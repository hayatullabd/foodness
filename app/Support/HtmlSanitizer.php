<?php

namespace App\Support;

class HtmlSanitizer
{
    /**
     * Strip active content from admin-authored HTML before it is rendered.
     */
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $clean = preg_replace('#<(script|iframe|object|embed|form|link|meta|base)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $clean = preg_replace('#<(script|iframe|object|embed|form|link|meta|base)\b[^>]*/>#is', '', $clean) ?? $clean;
        $clean = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1=$2#$2', $clean) ?? $clean;
        $clean = preg_replace('/(href|src)\s*=\s*javascript:[^\s>]*/i', '$1="#"', $clean) ?? $clean;

        return $clean;
    }
}
