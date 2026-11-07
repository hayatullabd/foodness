<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Throwable;

class ThemeCssPublisher
{
    /**
     * Publish store theme CSS to public/css so updates work without npm/Vite.
     *
     * @return array{ok: bool, message: string, path?: string}
     */
    public function publish(): array
    {
        $source = resource_path('css/foodness-theme.css');
        if (! File::exists($source)) {
            return [
                'ok' => false,
                'message' => 'Theme CSS source not found at resources/css/foodness-theme.css',
            ];
        }

        $targetDir = public_path('css');
        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true, true);
        }

        $target = $targetDir.DIRECTORY_SEPARATOR.'foodness-theme.css';
        File::copy($source, $target);

        $this->syncSharedDocumentRootCss($targetDir);

        return [
            'ok' => true,
            'message' => 'Theme CSS published successfully (no npm required).',
            'path' => $target,
        ];
    }

    /**
     * Shared hosting: document root is parent of laravel/, copy public/css there.
     */
    protected function syncSharedDocumentRootCss(string $sourceCssDir): void
    {
        try {
            $base = base_path();
            if (basename($base) !== 'laravel') {
                return;
            }

            $targetCssDir = dirname($base).DIRECTORY_SEPARATOR.'css';
            if (! File::isDirectory(dirname($targetCssDir))) {
                return;
            }

            if (! File::isDirectory($targetCssDir)) {
                File::makeDirectory($targetCssDir, 0755, true, true);
            }

            File::copyDirectory($sourceCssDir, $targetCssDir);
        } catch (Throwable) {
            // Soft-fail: Laravel public/css is still updated.
        }
    }
}
