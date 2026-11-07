<?php

namespace App\Support;

class InstallationHelper
{
    public static function isInstalled(): bool
    {
        return file_exists(storage_path('installed'));
    }

    public static function markInstalled(): void
    {
        file_put_contents(storage_path('installed'), json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => config('version.version', '1.0.0'),
        ], JSON_PRETTY_PRINT));
    }
}
