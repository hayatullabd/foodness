<?php

namespace App\Support;

/**
 * Normalizes admin-authored landing HTML/CSS so the built-in checkout
 * survives full-document pastes and aggressive universal CSS resets.
 */
class LandingHtml
{
    /**
     * Strip full-document wrappers and return body (or fragment) content.
     * Preserves <style> / absolute stylesheets from <head> so background/button
     * colors from pasted Elementor/HTML kits are not lost with the head strip.
     */
    public static function normalize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $preservedHeadAssets = self::extractHeadAssets($html);

        if (preg_match('/<body\b[^>]*>(.*)<\/body>/is', $html, $matches)) {
            $html = $matches[1];
        } else {
            $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html) ?? $html;
            $html = preg_replace('/<head\b[^>]*>.*?<\/head>/is', '', $html) ?? $html;
            $html = preg_replace('/<\/?html\b[^>]*>/i', '', $html) ?? $html;
            $html = preg_replace('/<\/?body\b[^>]*>/i', '', $html) ?? $html;
        }

        // Drop broken relative asset tags from exported HTML kits.
        $html = preg_replace('/<link\b[^>]*href=["\'](?:\.\/)?style\.css["\'][^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/<script\b[^>]*src=["\'](?:\.\/)?script\.js["\'][^>]*>\s*<\/script>/i', '', $html) ?? $html;

        return trim($preservedHeadAssets.$html);
    }

    /**
     * Pull color-bearing assets out of <head> before it is discarded.
     */
    private static function extractHeadAssets(string $html): string
    {
        if (! preg_match('/<head\b[^>]*>(.*?)<\/head>/is', $html, $headMatch)) {
            return '';
        }

        $head = $headMatch[1];
        $chunks = [];

        if (preg_match_all('/<style\b([^>]*)>(.*?)<\/style>/is', $head, $styleMatches, PREG_SET_ORDER)) {
            foreach ($styleMatches as $styleMatch) {
                $attrs = trim((string) ($styleMatch[1] ?? ''));
                $css = self::scopeCss((string) ($styleMatch[2] ?? ''));
                $chunks[] = $attrs !== ''
                    ? '<style '.$attrs.'>'.$css.'</style>'
                    : '<style>'.$css.'</style>';
            }
        }

        if (preg_match_all('/<link\b[^>]*>/i', $head, $linkMatches)) {
            foreach ($linkMatches[0] as $linkTag) {
                if (! preg_match('/rel\s*=\s*["\']stylesheet["\']/i', $linkTag)) {
                    continue;
                }
                // Keep absolute CSS only — relative kit files 404 on the live store.
                if (! preg_match('/href\s*=\s*["\'](https?:\/\/[^"\']+)["\']/i', $linkTag)) {
                    continue;
                }
                $chunks[] = $linkTag;
            }
        }

        return $chunks === [] ? '' : implode("\n", $chunks)."\n";
    }

    /**
     * Scope universal `*` resets to landing content so Tailwind checkout utilities win.
     */
    public static function scopeCss(string $css): string
    {
        $css = trim($css);
        if ($css === '') {
            return '';
        }

        // `* { ... }` → `.lp-content, .lp-content * { ... }`
        $scoped = preg_replace(
            '/(^|[\}\/\*])\s*\*\s*\{/u',
            '$1 .lp-content, .lp-content * {',
            $css
        );

        return is_string($scoped) ? $scoped : $css;
    }

    /**
     * Clean AI / pasted landing output into safe editor fields.
     *
     * @return array{html: string, css: string, js: string}
     */
    public static function sanitizeAiOutput(string $html, string $css = '', string $js = ''): array
    {
        $html = self::normalize($html);

        // Move any <style> blocks found in HTML into CSS field.
        if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $styleMatches)) {
            foreach ($styleMatches[1] as $i => $styleBody) {
                $css = trim($css."\n".trim((string) $styleBody));
                $html = str_replace($styleMatches[0][$i], '', $html);
            }
        }

        // Move script tags from HTML into JS (keep markup clean).
        if (preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $scriptMatches)) {
            foreach ($scriptMatches[1] as $i => $scriptBody) {
                // Skip external src scripts.
                if (preg_match('/\ssrc\s*=/i', $scriptMatches[0][$i])) {
                    $html = str_replace($scriptMatches[0][$i], '', $html);
                    continue;
                }
                $js = trim($js."\n".trim((string) $scriptBody));
                $html = str_replace($scriptMatches[0][$i], '', $html);
            }
        }

        $html = trim(preg_replace("/\n{3,}/", "\n\n", $html) ?? $html);

        if (! str_contains($html, '[checkout_form]')) {
            $html = rtrim($html)."\n\n[checkout_form]\n";
        }

        // Strip dangerous checkout targeting from CSS.
        $css = preg_replace('/#checkout-section[^{]*\{[^}]*\}/i', '', $css) ?? $css;
        $css = preg_replace('/#landing-order-form[^{]*\{[^}]*\}/i', '', $css) ?? $css;
        $css = LandingPageTemplate::normalizeCss($css);

        // Soft-block form hijacks in JS.
        if (preg_match('/landing-order-form|preventDefault\s*\(|\.submit\s*\(/i', $js)) {
            $js = "// AI JS trimmed: checkout form handlers are managed by the system.\n";
        }

        return [
            'html' => rtrim($html)."\n",
            'css' => rtrim($css)."\n",
            'js' => trim($js) === '' ? '' : rtrim($js)."\n",
        ];
    }

    /**
     * Wrap marketing HTML in .lp-content and keep checkout outside that reset scope.
     */
    public static function compose(string $contentHtml, string $checkoutHtml): string
    {
        $contentHtml = trim($contentHtml);
        $checkoutHtml = trim($checkoutHtml);

        if ($contentHtml === '') {
            return $checkoutHtml;
        }

        if (str_contains($contentHtml, '[checkout_form]')) {
            $parts = explode('[checkout_form]', $contentHtml, 2);
            $before = trim($parts[0] ?? '');
            $after = trim($parts[1] ?? '');

            return self::wrapContent($before)
                .$checkoutHtml
                .self::wrapContent($after);
        }

        return self::wrapContent($contentHtml)."\n".$checkoutHtml;
    }

    private static function wrapContent(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        return '<div class="lp-content">'.$html.'</div>';
    }
}
