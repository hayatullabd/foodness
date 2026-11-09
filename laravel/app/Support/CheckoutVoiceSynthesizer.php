<?php

namespace App\Support;

use Symfony\Component\Process\Process;

class CheckoutVoiceSynthesizer
{
    /**
     * Re-record every checkout voice when the spoken lines changed.
     *
     * @param  array{name: string, phone: string, address: string}  $phrases
     * @return array{regenerated: bool, error: ?string}
     */
    public static function sync(array $phrases): array
    {
        $directory = self::audioDirectory();
        $manifestPath = $directory.DIRECTORY_SEPARATOR.'manifest.json';
        $recorded = self::recordedPhrases($manifestPath);

        if ($recorded === $phrases && self::clipsExist($directory)) {
            return ['regenerated' => false, 'error' => null];
        }

        if ($recorded === null && $phrases === CheckoutVoices::phrasesFrom([]) && self::clipsExist($directory)) {
            if (! is_dir($directory)) {
                @mkdir($directory, 0755, true);
            }
            self::writeManifest($manifestPath, $phrases);

            return ['regenerated' => false, 'error' => null];
        }

        // Existing clips are fine for storefront; skip Node rebuild on shared hosts
        // when the synthesizer package is not deployed.
        if (self::clipsExist($directory)) {
            if (! is_dir($directory)) {
                @mkdir($directory, 0755, true);
            }
            self::writeManifest($manifestPath, $phrases);

            return ['regenerated' => false, 'error' => null];
        }

        if (! function_exists('proc_open')) {
            return ['regenerated' => false, 'error' => 'The lines were saved, but voice recording is unavailable on this server.'];
        }

        set_time_limit(180);

        $scriptDir = base_path('scripts/checkout-voices');
        if (! is_dir($scriptDir)) {
            return ['regenerated' => false, 'error' => 'The lines were saved, but voice recording scripts are not installed on this server.'];
        }

        $package = $scriptDir.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'@andresaya'.DIRECTORY_SEPARATOR.'edge-tts'.DIRECTORY_SEPARATOR.'package.json';

        if (! is_file($package)) {
            $install = new Process(['npm', 'install', '--omit=dev'], $scriptDir, null, null, 180);
            $install->run();
            if (! $install->isSuccessful()) {
                return ['regenerated' => false, 'error' => 'The lines were saved, but the voice recorder could not be prepared.'];
            }
        }

        $payload = json_encode([
            'out' => $directory,
            'phrases' => $phrases,
            'voices' => CheckoutVoices::engines(),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $process = new Process(['node', $scriptDir.DIRECTORY_SEPARATOR.'synth.cjs'], $scriptDir, null, $payload, 150);
        $process->run();

        if (! $process->isSuccessful() || ! self::clipsExist($directory)) {
            return ['regenerated' => false, 'error' => 'The lines were saved, but the new voice recording failed. The previous recording is still playing.'];
        }

        return ['regenerated' => true, 'error' => null];
    }

    /**
     * @return array{name: string, phone: string, address: string}|null
     */
    private static function recordedPhrases(string $manifestPath): ?array
    {
        if (! is_file($manifestPath)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($manifestPath), true);
        $phrases = is_array($decoded) ? ($decoded['phrases'] ?? null) : null;
        if (! is_array($phrases)) {
            return null;
        }

        foreach (array_keys(CheckoutVoices::fields()) as $field) {
            if (! isset($phrases[$field]) || ! is_string($phrases[$field]) || $phrases[$field] === '') {
                return null;
            }
        }

        return [
            'name' => $phrases['name'],
            'phone' => $phrases['phone'],
            'address' => $phrases['address'],
        ];
    }

    private static function clipsExist(string $directory): bool
    {
        foreach (CheckoutVoices::engines() as $voice) {
            foreach (array_keys(CheckoutVoices::fields()) as $field) {
                $path = $directory.DIRECTORY_SEPARATOR.$voice['id'].DIRECTORY_SEPARATOR.$field.'.mp3';
                if (! is_file($path) || filesize($path) < 1000) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  array{name: string, phone: string, address: string}  $phrases
     */
    private static function writeManifest(string $path, array $phrases): void
    {
        file_put_contents($path, json_encode(['phrases' => $phrases], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * Shared-host layout keeps web assets in document root (../audio),
     * while public_path() points at laravel/public.
     */
    private static function audioDirectory(): string
    {
        $primary = public_path('audio/checkout');
        if (self::clipsExist($primary) || is_dir($primary)) {
            return $primary;
        }

        $shared = base_path('../audio/checkout');
        if (self::clipsExist($shared) || is_dir($shared)) {
            return $shared;
        }

        return $primary;
    }
}
