<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'enabled',
    'hours',
    'max_per_product',
    'block_ip_enabled',
    'blocked_ips',
    'max_per_ip',
    'block_phone_enabled',
    'blocked_phones',
    'max_per_phone',
    'validate_bd_phone',
    'block_device_enabled',
    'blocked_devices',
    'max_per_device',
    'block_words_enabled',
    'blocked_words',
    'min_address_length',
])]
class OrderRestrictionSetting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'hours' => 'integer',
            'max_per_product' => 'integer',
            'block_ip_enabled' => 'boolean',
            'max_per_ip' => 'integer',
            'block_phone_enabled' => 'boolean',
            'max_per_phone' => 'integer',
            'validate_bd_phone' => 'boolean',
            'block_device_enabled' => 'boolean',
            'max_per_device' => 'integer',
            'block_words_enabled' => 'boolean',
            'min_address_length' => 'integer',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'enabled' => false,
            'hours' => 24,
            'max_per_product' => 2,
            'block_ip_enabled' => false,
            'blocked_ips' => null,
            'max_per_ip' => 3,
            'block_phone_enabled' => false,
            'blocked_phones' => null,
            'max_per_phone' => 2,
            'validate_bd_phone' => true,
            'block_device_enabled' => false,
            'blocked_devices' => null,
            'max_per_device' => 2,
            'block_words_enabled' => false,
            'blocked_words' => null,
            'min_address_length' => 10,
        ]);
    }

    /**
     * Parse comma or newline separated values into trimmed array.
     *
     * @return array<int, string>
     */
    public function parseList(?string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $raw) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '')
            ->values()
            ->all();
    }
}
