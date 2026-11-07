<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'site_title',
    'meta_description',
    'meta_keywords',
    'google_site_verification',
])]
class SeoSetting extends Model
{
    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'site_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'google_site_verification' => null,
        ]);
    }

    /**
     * @return array{
     *     site_title: ?string,
     *     meta_description: ?string,
     *     meta_keywords: ?string,
     *     google_site_verification: ?string
     * }
     */
    public static function cached(): array
    {
        return Cache::remember('store.seo_settings', 60, function (): array {
            $settings = static::query()->first();

            return [
                'site_title' => $settings?->site_title,
                'meta_description' => $settings?->meta_description,
                'meta_keywords' => $settings?->meta_keywords,
                'google_site_verification' => $settings?->google_site_verification,
            ];
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('store.seo_settings');
    }

    /**
     * @return list<string>
     */
    public function keywordList(): array
    {
        if (blank($this->meta_keywords)) {
            return [];
        }

        return collect(preg_split('/\s*,\s*/', $this->meta_keywords) ?: [])
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->values()
            ->all();
    }
}
