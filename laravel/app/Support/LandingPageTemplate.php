<?php

namespace App\Support;

/**
 * Premium landing starter (fragment HTML + CSS).
 * Not a full-document paste — uses [checkout_form] and theme-safe CSS.
 */
class LandingPageTemplate
{
    public static function html(): string
    {
        $path = resource_path('views/landing/templates/standard.html');
        if (is_file($path)) {
            return rtrim((string) file_get_contents($path))."\n";
        }

        return "[checkout_form]\n";
    }

    public static function css(): string
    {
        $path = resource_path('views/landing/templates/standard.css');
        if (is_file($path)) {
            return self::normalizeCss((string) file_get_contents($path));
        }

        return "html { scroll-behavior: smooth; }\n";
    }

    /**
     * Fix common paste/format issues without changing visual design.
     */
    public static function normalizeCss(string $css): string
    {
        $css = trim($css);
        // Repair broken Google Fonts @import split across lines.
        $css = preg_replace(
            "/@import\s+url\('https:\/\/fonts\.googleapis\.com\/css2\?family=Hind\+Siliguri:wght@400;\s*\n\s*500;600;700&display=swap'\);/u",
            "@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap');",
            $css
        ) ?? $css;

        $css = preg_replace(
            "/@import\s+url\('https:\/\/fonts\.googleapis\.com\/css2\?family=Anek\+Bangla:wght@400;\s*\n\s*500;600;700&family=Tiro\+Bangla:ital@0;1&display=swap'\);/u",
            "@import url('https://fonts.googleapis.com/css2?family=Anek+Bangla:wght@400;500;600;700&family=Tiro+Bangla:ital@0;1&display=swap');",
            $css
        ) ?? $css;

        // Prefer theme tokens when present on the landing page.
        if (! str_contains($css, '--lp-theme')) {
            $css = preg_replace(
                '/(--primary-color:\s*)(#[0-9a-fA-F]{3,8})/',
                '$1var(--lp-theme, $2)',
                $css,
                1
            ) ?? $css;
        }

        return trim($css)."\n";
    }
}
