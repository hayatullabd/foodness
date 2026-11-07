<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provider', 'credentials', 'is_active'])]
#[Hidden(['credentials'])]
class ApiIntegration extends Model
{
    public const PROVIDER_COD = 'cod';

    public const PROVIDER_SSLCOMMERZ = 'sslcommerz';

    public const PROVIDER_MANUAL_PAYMENT = 'manual_payment';

    public const PROVIDER_SMS_GATEWAY = 'sms_gateway';

    public const PROVIDER_FACEBOOK_CAPI = 'facebook_capi';

    public const PROVIDER_GEMINI_AI = 'gemini_ai';

    public const PROVIDER_SOCIAL_LOGIN = 'social_login';

    public const PROVIDER_GEMINI_ASSISTANT = 'gemini_assistant';

    public const PROVIDER_FACEBOOK_PIXEL = 'facebook_pixel';

    public const PROVIDER_GOOGLE_TRACKING = 'google_tracking';

    public const PROVIDER_TIKTOK_PIXEL = 'tiktok_pixel';

    /**
     * Credential keys that should be masked and kept when blank on update.
     *
     * @var array<string, list<string>>
     */
    public const SECRET_KEYS = [
        self::PROVIDER_COD => [],
        self::PROVIDER_SSLCOMMERZ => ['store_password'],
        self::PROVIDER_SMS_GATEWAY => ['api_key'],
        self::PROVIDER_FACEBOOK_CAPI => ['access_token'],
        self::PROVIDER_GEMINI_AI => ['api_key'],
        self::PROVIDER_SOCIAL_LOGIN => ['google_client_secret', 'facebook_client_secret'],
        self::PROVIDER_GEMINI_ASSISTANT => ['api_key'],
        self::PROVIDER_MANUAL_PAYMENT => [],
        self::PROVIDER_FACEBOOK_PIXEL => [],
        self::PROVIDER_GOOGLE_TRACKING => ['ga4_api_secret'],
        self::PROVIDER_TIKTOK_PIXEL => ['access_token'],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function forProvider(string $provider): self
    {
        return static::query()->firstOrCreate(
            ['provider' => $provider],
            [
                'credentials' => [],
                'is_active' => false,
            ],
        );
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials ?? [], $key, $default);
    }

    public function maskedCredential(string $key): ?string
    {
        $value = $this->credential($key);

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
     * Merge incoming credentials, keeping existing secret values when the input is blank.
     *
     * @param  array<string, mixed>  $incoming
     * @param  list<string>  $secretKeys
     */
    public function mergeCredentials(array $incoming, array $secretKeys = []): void
    {
        $credentials = $this->credentials ?? [];

        foreach ($incoming as $key => $value) {
            if (in_array($key, $secretKeys, true)) {
                $trimmed = is_string($value) ? trim($value) : $value;

                if ($trimmed === null || $trimmed === '') {
                    continue;
                }

                $credentials[$key] = $trimmed;

                continue;
            }

            $credentials[$key] = is_string($value) ? trim($value) : $value;
        }

        $this->credentials = $credentials;
    }

    /**
     * @return list<string>
     */
    public function secretKeys(): array
    {
        return self::SECRET_KEYS[$this->provider] ?? [];
    }

    /**
     * Remove one or more credential keys (used for revoke/clear actions).
     *
     * @param  list<string>  $keys
     */
    public function forgetCredentials(array $keys): void
    {
        $credentials = $this->credentials ?? [];

        foreach ($keys as $key) {
            unset($credentials[$key]);
        }

        $this->credentials = $credentials;
    }

    /**
     * Wipe all credentials and deactivate the integration.
     */
    public function revokeAll(): void
    {
        $this->credentials = [];
        $this->is_active = false;
    }
}
