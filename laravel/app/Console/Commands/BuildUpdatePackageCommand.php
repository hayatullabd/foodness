<?php

namespace App\Console\Commands;

use App\Support\ThemeCssPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;
use ZipArchive;

class BuildUpdatePackageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:build-update 
                            {version : The new version number (e.g. 1.1.0)} 
                            {--changelog= : Summary of changes in this release}
                            {--skip-vite : Skip npm run build even if Node is available}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Package core code files into a clean, safe update ZIP archive ready for client stores';

    /**
     * Directories strictly included in the core update package.
     *
     * @var list<string>
     */
    protected array $includePaths = [
        'app',
        'bootstrap',
        'config',
        'database/migrations',
        'resources',
        'routes',
        'public/build',
        'public/css',
        // Landing pages need Tailwind Play CDN (v3). v4 browser.js loses to admin CSS resets.
        'public/vendor/tailwindcss',
    ];

    /**
     * Files strictly included at root level.
     *
     * @var list<string>
     */
    protected array $includeFiles = [
        'composer.json',
    ];

    /**
     * Paths strictly excluded from the update package.
     *
     * @var list<string>
     */
    protected array $excludePatterns = [
        '.git',
        '.github',
        '.idea',
        '.vscode',
        '.claude',
        '.cursor',
        '.env',
        'storage',
        'vendor',
        'node_modules',
        'tests',
        'public/uploads',
        'public/storage',
        'database/database.sqlite',
        'resources/views/themes/custom',
    ];

    public function handle(): int
    {
        $version = trim((string) $this->argument('version'));
        $changelog = (string) $this->option('changelog');

        if (! preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/', $version)) {
            $this->error("Invalid version format: '{$version}'. Please use Semantic Versioning (e.g. 1.1.0).");

            return self::FAILURE;
        }

        if (! class_exists(ZipArchive::class)) {
            $this->error('PHP Zip extension is required to generate update packages.');

            return self::FAILURE;
        }

        $outputDir = storage_path('app/updates');
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true, true);
        }

        $zipFileName = "update_v{$version}.zip";
        $zipFilePath = $outputDir.DIRECTORY_SEPARATOR.$zipFileName;

        if (File::exists($zipFilePath)) {
            if (! $this->confirm("File '{$zipFileName}' already exists. Do you want to overwrite it?", true)) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
            @unlink($zipFilePath);
        }

        if (! $this->option('skip-vite')) {
            $this->runViteBuildIfAvailable();
        }

        $this->info('Publishing theme CSS (npm not required)...');
        $publish = app(ThemeCssPublisher::class)->publish();
        if (! $publish['ok']) {
            $this->warn($publish['message']);
        } else {
            $this->line('  ✓ '.$publish['message']);
        }

        $this->info("Packaging core files for version v{$version}...");

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Could not create ZIP archive at: {$zipFilePath}");

            return self::FAILURE;
        }

        // Add version.json manifest
        $manifest = [
            'version' => $version,
            'build' => date('YmdHis'),
            'release_date' => date('Y-m-d'),
            'changelog' => $changelog,
            'packaged_at' => now()->toIso8601String(),
        ];
        $zip->addFromString('version.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $fileCount = 1;
        $basePath = base_path();

        // 1. Add individual root files
        foreach ($this->includeFiles as $relFile) {
            $absFile = $basePath.DIRECTORY_SEPARATOR.$relFile;
            if (File::exists($absFile)) {
                $zip->addFile($absFile, $relFile);
                $fileCount++;
            }
        }

        // 2. Add included directories recursively
        foreach ($this->includePaths as $relDir) {
            $absDir = $basePath.DIRECTORY_SEPARATOR.$relDir;
            if (! File::isDirectory($absDir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($basePath) + 1);
                $normalizedPath = str_replace('\\', '/', $relativePath);

                if ($this->isExcluded($normalizedPath)) {
                    continue;
                }

                $zip->addFile($filePath, $normalizedPath);
                $fileCount++;
            }
        }

        $zip->close();

        $sizeBytes = filesize($zipFilePath);
        $sizeFormatted = round($sizeBytes / 1024 / 1024, 2).' MB';

        $this->newLine();
        $this->info('🎉 Update package generated successfully!');
        $this->table(['Detail', 'Value'], [
            ['Version', "v{$version}"],
            ['File Count', $fileCount.' files'],
            ['Archive Size', $sizeFormatted],
            ['File Location', $zipFilePath],
        ]);

        $this->newLine();
        $this->line('Store owners can upload this ZIP file via <info>Settings > System Update</info> to upgrade safely.');

        return self::SUCCESS;
    }

    protected function isExcluded(string $path): bool
    {
        $normalized = str_replace('\\', '/', trim($path, '/'));

        foreach ($this->excludePatterns as $pattern) {
            $normPattern = str_replace('\\', '/', trim($pattern, '/'));

            if ($normalized === $normPattern || str_starts_with($normalized, $normPattern.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function runViteBuildIfAvailable(): void
    {
        $npm = $this->resolveNpmBinary();
        if ($npm === null) {
            $this->warn('Node/npm not found — skipping Vite build. Theme CSS will still be published to public/css.');

            return;
        }

        $this->info('Running npm run build...');
        $process = new Process([$npm, 'run', 'build'], base_path(), null, null, 300);
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->warn('Vite build failed — continuing with published theme CSS + existing public/build.');
        }
    }

    protected function resolveNpmBinary(): ?string
    {
        foreach (['npm', 'npm.cmd'] as $binary) {
            $process = Process::fromShellCommandline(
                PHP_OS_FAMILY === 'Windows' ? "where {$binary}" : "command -v {$binary}"
            );
            $process->run();
            if ($process->isSuccessful()) {
                $line = trim(explode("\n", str_replace("\r", '', $process->getOutput()))[0] ?? '');
                if ($line !== '') {
                    return $line;
                }
            }
        }

        return null;
    }
}
