<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class StoreSettings
{
    /**
     * @return array<string, string|null>
     */
    public static function all(): array
    {
        return Cache::remember('store.settings', 60, function (): array {
            return Setting::query()
                ->pluck('value', 'key')
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function forget(): void
    {
        Cache::forget('store.settings');
    }
}
