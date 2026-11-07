<?php

namespace App\Services\Update;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class SystemUpdateService
{
    /**
     * Files and directories strictly protected from being overwritten by any update.
     *
     * @var list<string>
     */
    protected array $protectedPaths = [
        '.env',
        'storage',
        'storage/',
        'public/uploads',
        'public/uploads/',
        'public/storage',
        'database/database.sqlite',
        'resources/views/themes/custom',
        'storage/installed',
    ];

    /**
     * Check if a new version is available from GitHub Releases or central update server.
     *
     * @return array{
     *     has_update: bool,
     *     current_version: string,
     *     latest_version?: string,
     *     release_date?: string,
     *     changelog?: string,
     *     download_url?: string,
     *     source?: string,
     *     message?: string,
     * }
     */
    public function checkUpdate(): array
    {
        $githubRepo = config('version.github_repo');

        if (filled($githubRepo)) {
            return $this->checkGitHubRelease($githubRepo);
        }

        return $this->checkCustomUpdateServer();
    }

    /**
     * Check GitHub Releases for updates.
     *
     * @return array{
     *     has_update: bool,
     *     current_version: string,
     *     latest_version?: string,
     *     release_date?: string,
     *     changelog?: string,
     *     download_url?: string,
     *     source?: string,
     *     message?: string,
     * }
     */
    public function checkGitHubRelease(string $repo): array
    {
        $currentVersion = (string) config('version.version', '1.0.0');
        $token = config('version.github_token');

        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'Foodness-Store-Updater',
        ];

        if (filled($token)) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        try {
            $apiUrl = 'https://api.github.com/repos/'.trim($repo, '/').'/releases/latest';
            $response = Http::timeout(8)->withHeaders($headers)->get($apiUrl);

            if ($response->successful()) {
                $release = $response->json();
                $tagName = (string) ($release['tag_name'] ?? '');
                $cleanLatestVersion = ltrim($tagName, 'v');

                // Find the update zip asset in release assets
                $downloadUrl = null;
                $assets = $release['assets'] ?? [];

                foreach ($assets as $asset) {
                    if (str_ends_with(strtolower($asset['name'] ?? ''), '.zip')) {
                        $downloadUrl = $asset['browser_download_url'] ?? $asset['url'] ?? null;
                        break;
                    }
                }

                // If no zip asset found, fallback to GitHub's zipball archive
                if (! $downloadUrl) {
                    $downloadUrl = $release['zipball_url'] ?? null;
                }

                return [
                    'has_update' => version_compare($cleanLatestVersion, $currentVersion, '>'),
                    'current_version' => $currentVersion,
                    'latest_version' => $cleanLatestVersion,
                    'release_date' => isset($release['published_at']) ? substr($release['published_at'], 0, 10) : null,
                    'changelog' => $release['body'] ?? '',
                    'download_url' => $downloadUrl,
                    'source' => 'GitHub Releases ('.$repo.')',
                ];
            }

            if ($response->status() === 404) {
                return [
                    'has_update' => false,
                    'current_version' => $currentVersion,
                    'message' => "No releases found on GitHub for repository '{$repo}'.",
                ];
            }
        } catch (Throwable $e) {
            Log::warning('GitHub release check failed.', ['error' => $e->getMessage()]);
        }

        return [
            'has_update' => false,
            'current_version' => $currentVersion,
            'message' => 'Could not connect to GitHub to check for updates. You can update manually using the ZIP upload option.',
        ];
    }

    /**
     * Check custom self-hosted update server.
     *
     * @return array{
     *     has_update: bool,
     *     current_version: string,
     *     latest_version?: string,
     *     release_date?: string,
     *     changelog?: string,
     *     download_url?: string,
     *     message?: string,
     * }
     */
    protected function checkCustomUpdateServer(): array
    {
        $currentVersion = (string) config('version.version', '1.0.0');
        $serverUrl = config('version.update_server_url');

        if (blank($serverUrl)) {
            return [
                'has_update' => false,
                'current_version' => $currentVersion,
                'message' => 'No GitHub repository or update server configured. You can update manually by uploading an update ZIP package.',
            ];
        }

        try {
            $response = Http::timeout(5)->get(rtrim($serverUrl, '/').'/check', [
                'current_version' => $currentVersion,
                'domain' => request()->getHost(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $latestVersion = (string) ($data['latest_version'] ?? $currentVersion);

                return [
                    'has_update' => version_compare($latestVersion, $currentVersion, '>'),
                    'current_version' => $currentVersion,
                    'latest_version' => $latestVersion,
                    'release_date' => $data['release_date'] ?? null,
                    'changelog' => $data['changelog'] ?? '',
                    'download_url' => $data['download_url'] ?? null,
                    'source' => 'Update Server',
                ];
            }
        } catch (Throwable $e) {
            Log::warning('Central update server check failed.', ['error' => $e->getMessage()]);
        }

        return [
            'has_update' => false,
            'current_version' => $currentVersion,
            'message' => 'Could not reach update server. You can update manually using the ZIP upload option.',
        ];
    }

    /**
     * Download an update package from a URL safely (supports GitHub authorization tokens).
     */
    public function downloadPackage(string $url, string $destinationPath): bool
    {
        $token = config('version.github_token');
        $headers = [
            'User-Agent' => 'Foodness-Store-Updater',
        ];

        if (str_contains($url, 'api.github.com') && filled($token)) {
            $headers['Accept'] = 'application/octet-stream';
            $headers['Authorization'] = 'Bearer '.$token;
        }

        $response = Http::timeout(180)
            ->withHeaders($headers)
            ->sink($destinationPath)
            ->get($url);

        return $response->successful() && File::exists($destinationPath) && filesize($destinationPath) > 0;
    }

    /**
     * Automatically check and apply update in the background (used by Cron).
     *
     * @return array{ok: bool, message: string}
     */
    public function checkAndApplyAutoUpdate(): array
    {
        $check = $this->checkUpdate();

        if (! ($check['has_update'] ?? false) || blank($check['download_url'] ?? null)) {
            return [
                'ok' => false,
                'message' => 'No update available to auto-apply.',
            ];
        }

        $tempPath = storage_path('app/auto_update_'.time().'.zip');

        try {
            if (! $this->downloadPackage($check['download_url'], $tempPath)) {
                @unlink($tempPath);

                return [
                    'ok' => false,
                    'message' => 'Could not download automatic update package.',
                ];
            }

            $result = $this->applyZipUpdate($tempPath);
            @unlink($tempPath);

            Log::info('Automatic system update finished.', ['result' => $result]);

            return $result;
        } catch (Throwable $e) {
            @unlink($tempPath);
            Log::error('Automatic system update encountered exception.', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'message' => 'Auto-update error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Apply an update package from an uploaded or downloaded ZIP archive.
     *
     * @return array{ok: bool, message: string, new_version?: string}
     */
    public function applyZipUpdate(UploadedFile|string $zipSource): array
    {
        if (! class_exists(ZipArchive::class)) {
            return [
                'ok' => false,
                'message' => 'PHP Zip extension is required to extract update packages.',
            ];
        }

        $tempPath = is_string($zipSource)
            ? $zipSource
            : $zipSource->getRealPath();

        $zip = new ZipArchive;
        if ($zip->open($tempPath) !== true) {
            return [
                'ok' => false,
                'message' => 'Could not read update ZIP file. The archive might be corrupted.',
            ];
        }

        // 1. Validate archive structure
        $extractedVersion = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat && $stat['name'] === 'version.json') {
                $jsonContent = $zip->getFromIndex($i);
                $decoded = json_decode((string) $jsonContent, true);
                $extractedVersion = $decoded['version'] ?? null;
            }
        }

        $maintenanceSecret = Str::random(40);

        // 2. Put application into maintenance mode safely
        try {
            Artisan::call('down', ['--secret' => $maintenanceSecret]);
        } catch (Throwable $e) {
            Log::warning('Maintenance mode command failed softly.', ['error' => $e->getMessage()]);
        }

        try {
            // 3. Extract only non-protected core files
            $basePath = realpath(base_path());
            if ($basePath === false) {
                throw new RuntimeException('Application path could not be resolved.');
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if ($entry === false || str_ends_with($entry, '/')) {
                    continue;
                }

                $relative = $this->safeRelativePath($entry);

                if ($this->isProtectedPath($relative)) {
                    continue;
                }

                $destination = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
                $destinationDir = dirname($destination);
                $resolvedDir = realpath($destinationDir);

                if ($resolvedDir !== false && ! str_starts_with($resolvedDir, $basePath)) {
                    throw new RuntimeException('Rejected ZIP entry.');
                }

                if (! File::isDirectory($destinationDir)) {
                    File::makeDirectory($destinationDir, 0755, true, true);
                }

                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    File::put($destination, $content);
                }
            }

            $zip->close();

            // 4. Update version in config/version.php if version.json existed in package
            if ($extractedVersion) {
                $this->persistNewVersion($extractedVersion);
            }

            // 5. Execute safe migrations
            Artisan::call('migrate', ['--force' => true]);

            // 6. Clear optimize and view cache
            Artisan::call('optimize:clear');

            // 7. Bring application out of maintenance mode
            Artisan::call('up');

            return [
                'ok' => true,
                'message' => 'System updated successfully! All files and database structures have been synchronized.',
                'new_version' => $extractedVersion ?? config('version.version'),
            ];
        } catch (Throwable $e) {
            // Ensure application is brought back up even if an error occurred
            try {
                Artisan::call('up');
            } catch (Throwable) {
                // Ignore
            }

            Log::error('System update failed during extraction or migration.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'message' => 'Update encountered an error: '.$e->getMessage().' If the store stayed in maintenance, open /'.$maintenanceSecret,
            ];
        }
    }

    /**
     * Check if a relative path inside the zip matches any protected user directory.
     */
    protected function isProtectedPath(string $path): bool
    {
        $normalized = str_replace('\\', '/', trim($path, '/'));

        foreach ($this->protectedPaths as $protected) {
            $protectedNorm = str_replace('\\', '/', trim($protected, '/'));

            if ($normalized === $protectedNorm || str_starts_with($normalized, $protectedNorm.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function safeRelativePath(string $entry): string
    {
        $entry = str_replace('\\', '/', $entry);

        if ($entry === '' || str_contains($entry, "\0") || str_starts_with($entry, '/') || preg_match('#(^|/)\.\.(/|$)#', $entry)) {
            throw new RuntimeException('Rejected ZIP entry.');
        }

        return ltrim($entry, '/');
    }

    /**
     * Persist the updated version number in config/version.php.
     */
    protected function persistNewVersion(string $newVersion): void
    {
        if (! preg_match('/^[0-9A-Za-z._-]{1,40}$/', $newVersion)) {
            return;
        }
        $configFile = config_path('version.php');
        if (! File::exists($configFile)) {
            return;
        }

        $content = File::get($configFile);
        $updated = preg_replace(
            "/'version'\s*=>\s*'[^']+'/",
            "'version' => '{$newVersion}'",
            $content
        );

        if ($updated) {
            File::put($configFile, $updated);
        }
    }
}
