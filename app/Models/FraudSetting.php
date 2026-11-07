<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provider', 'api_key', 'provider_keys', 'is_active'])]
#[Hidden(['api_key', 'provider_keys'])]
class FraudSetting extends Model
{
    public const PROVIDER_BDCOURIER = 'bdcourier';

    public const PROVIDER_HOORIN = 'hoorin';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'provider_keys' => 'array',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'provider' => self::PROVIDER_BDCOURIER,
            'api_key' => null,
            'provider_keys' => [],
            'is_active' => false,
        ]);
    }

    public function activeProvider(): string
    {
        return $this->provider ?: self::PROVIDER_BDCOURIER;
    }

    public function isConfigured(): bool
    {
        return $this->is_active && filled($this->keyForProvider($this->activeProvider()));
    }

    /**
     * Returns the API key for the given provider (or the currently active provider).
     */
    public function keyForProvider(?string $provider = null): ?string
    {
        $provider = $provider ?: $this->activeProvider();
        $keys = is_array($this->provider_keys) ? $this->provider_keys : [];

        if (! empty($keys[$provider])) {
            return (string) $keys[$provider];
        }

        // Backward compatibility: if active provider is bdcourier and provider_keys is empty, use legacy api_key
        if ($provider === self::PROVIDER_BDCOURIER && filled($this->api_key)) {
            return (string) $this->api_key;
        }

        return null;
    }

    public function maskedApiKey(?string $provider = null): ?string
    {
        $key = $this->keyForProvider($provider);

        if (blank($key)) {
            return null;
        }

        $length = strlen($key);

        if ($length <= 4) {
            return str_repeat('•', $length);
        }

        return str_repeat('•', max(8, $length - 4)).substr($key, -4);
    }
}
