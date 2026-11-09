<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'provider',
    'name',
    'api_key',
    'secret_key',
    'username',
    'password',
    'base_url',
    'token',
    'meta',
    'is_active',
    'is_preferred',
])]
#[Hidden(['api_key', 'secret_key', 'password', 'token'])]
class CourierApi extends Model
{
    public const PROVIDER_STEADFAST = 'steadfast';

    public const PROVIDER_PATHAO = 'pathao';

    public const PROVIDER_REDX = 'redx';

    public const PROVIDER_CARRYBEE = 'carrybee';

    public const PROVIDERS = [
        self::PROVIDER_STEADFAST,
        self::PROVIDER_PATHAO,
        self::PROVIDER_REDX,
        self::PROVIDER_CARRYBEE,
    ];

    public const PROVIDER_LABELS = [
        self::PROVIDER_STEADFAST => 'STEADFAST',
        self::PROVIDER_PATHAO => 'PATHAO',
        self::PROVIDER_REDX => 'REDX',
        self::PROVIDER_CARRYBEE => 'CARRYBEE',
    ];

    public const DEFAULT_BASE_URLS = [
        self::PROVIDER_STEADFAST => 'https://portal.packzy.com/api/v1',
        self::PROVIDER_PATHAO => 'https://api-hermes.pathao.com',
        self::PROVIDER_REDX => 'https://openapi.redx.com.bd',
        self::PROVIDER_CARRYBEE => 'https://developers.carrybee.com',
    ];

    public const REDX_BASE_URLS = [
        'production' => 'https://openapi.redx.com.bd',
        'sandbox' => 'https://sandbox.redx.com.bd',
    ];

    public const CARRYBEE_BASE_URLS = [
        'production' => 'https://developers.carrybee.com',
        'sandbox' => 'https://sandbox.carrybee.com',
    ];

    /**
     * Credential attribute names that stay when the form submits a blank value.
     *
     * @var list<string>
     */
    public const SECRET_ATTRIBUTES = [
        'api_key',
        'secret_key',
        'password',
        'token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_preferred' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        $meta = is_array($this->meta) ? $this->meta : [];

        return $meta[$key] ?? $default;
    }

    public function displayName(): string
    {
        if (filled($this->name)) {
            return (string) $this->name;
        }

        return self::PROVIDER_LABELS[$this->provider] ?? ucfirst((string) $this->provider);
    }

    public function providerBadge(): string
    {
        return self::PROVIDER_LABELS[$this->provider] ?? strtoupper((string) $this->provider);
    }

    public function sendsProductNames(): bool
    {
        return (bool) $this->metaValue('send_product_names', true);
    }

    public function defaultShippingNote(): ?string
    {
        $note = $this->metaValue('default_shipping_note');

        return filled($note) ? (string) $note : null;
    }

    public function shippingNoteFor(Order $order): ?string
    {
        if (filled($order->notes)) {
            return (string) $order->notes;
        }

        return $this->defaultShippingNote();
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function preferred(Builder $query): Builder
    {
        return $query->where('is_preferred', true);
    }

    /**
     * Resolve an existing method for the provider, or create a named default row.
     * Prefer preferred → active → oldest id so legacy single-account code keeps working.
     */
    public static function forProvider(string $provider): self
    {
        $provider = strtolower(trim($provider));

        $existing = static::query()
            ->where('provider', $provider)
            ->orderByDesc('is_preferred')
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'provider' => $provider,
            'name' => match ($provider) {
                self::PROVIDER_STEADFAST => 'Steadfast',
                self::PROVIDER_PATHAO => 'Pathao',
                self::PROVIDER_REDX => 'RedX',
                self::PROVIDER_CARRYBEE => 'Carrybee Courier',
                default => ucfirst($provider),
            },
            'base_url' => self::DEFAULT_BASE_URLS[$provider] ?? null,
            'is_active' => false,
            'is_preferred' => true,
        ]);
    }

    public static function resolveForSend(?int $courierApiId, ?string $provider = null): ?self
    {
        if ($courierApiId !== null) {
            return static::query()->active()->whereKey($courierApiId)->first();
        }

        if ($provider === null || $provider === '') {
            return null;
        }

        return static::query()
            ->active()
            ->whereRaw('LOWER(provider) = ?', [strtolower($provider)])
            ->orderByDesc('is_preferred')
            ->orderBy('id')
            ->first();
    }

    public function markPreferredExclusive(): void
    {
        if (! $this->is_preferred) {
            return;
        }

        static::query()
            ->where('provider', $this->provider)
            ->where('id', '!=', $this->id)
            ->where('is_preferred', true)
            ->update(['is_preferred' => false]);
    }

    public function maskedAttribute(string $attribute): ?string
    {
        $value = $this->getAttribute($attribute);

        if (blank($value)) {
            return null;
        }

        $secret = (string) $value;
        $length = strlen($secret);

        if ($length <= 4) {
            return str_repeat('•', $length);
        }

        return str_repeat('•', max(8, $length - 4)).substr($secret, -4);
    }

    /**
     * Merge incoming attributes, keeping existing secrets when the input is blank.
     *
     * @param  array<string, mixed>  $incoming
     * @param  list<string>  $secretKeys
     */
    public function mergeSecrets(array $incoming, array $secretKeys = self::SECRET_ATTRIBUTES): void
    {
        foreach ($incoming as $key => $value) {
            if (in_array($key, $secretKeys, true)) {
                $trimmed = is_string($value) ? trim($value) : $value;

                if ($trimmed === null || $trimmed === '') {
                    continue;
                }

                $this->setAttribute($key, $trimmed);

                continue;
            }

            $this->setAttribute($key, is_string($value) ? trim($value) : $value);
        }
    }

    public function webhookUrl(): ?string
    {
        return match ($this->provider) {
            self::PROVIDER_STEADFAST => rtrim((string) config('app.url'), '/').'/api/steadfast/webhook',
            self::PROVIDER_REDX => rtrim((string) config('app.url'), '/').'/api/redx/webhook',
            self::PROVIDER_CARRYBEE => rtrim((string) config('app.url'), '/').'/api/carrybee/webhook',
            default => null,
        };
    }

    public function redxEnvironment(): string
    {
        $base = strtolower((string) ($this->base_url ?? ''));

        if (str_contains($base, 'sandbox')) {
            return 'sandbox';
        }

        return 'production';
    }

    public function carrybeeEnvironment(): string
    {
        $base = strtolower((string) ($this->base_url ?? ''));

        if (str_contains($base, 'sandbox')) {
            return 'sandbox';
        }

        return 'production';
    }

    public static function normalizeBearerToken(?string $token): ?string
    {
        if ($token === null) {
            return null;
        }

        $token = trim(preg_replace('/^Bearer\s+/i', '', $token) ?? '');

        return $token === '' ? null : $token;
    }

    public static function normalizeBaseUrl(?string $url, ?string $fallback = null): ?string
    {
        $url = trim((string) ($url ?: $fallback ?: ''));

        if ($url === '') {
            return null;
        }

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://'.$url;
        }

        return rtrim($url, '/');
    }

    /**
     * A webhook is accepted only when a stored token matches the provided bearer.
     * Missing secrets are rejected so an unconfigured method cannot be spoofed.
     */
    public static function webhookTokenMatches(string $provider, ?string $providedToken): bool
    {
        $expectedTokens = static::query()
            ->where('provider', strtolower($provider))
            ->whereNotNull('token')
            ->pluck('token')
            ->map(fn ($token) => self::normalizeBearerToken(is_string($token) ? $token : null))
            ->filter()
            ->values();

        if ($expectedTokens->isEmpty()) {
            return false;
        }

        $provided = self::normalizeBearerToken($providedToken);

        if ($provided === null) {
            return false;
        }

        return $expectedTokens->contains(fn (string $expected) => hash_equals($expected, $provided));
    }
}
