<?php

namespace App\Models;

use App\Casts\SafeEncryptedString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AiProvider extends Model
{
    public const DRIVER_GEMINI = 'gemini';

    public const DRIVER_OPENAI = 'openai';

    public const DRIVER_ANTHROPIC = 'anthropic';

    public const DRIVER_OPENAI_COMPATIBLE = 'openai_compatible';

    /**
     * @var list<string>
     */
    public const DRIVERS = [
        self::DRIVER_GEMINI,
        self::DRIVER_OPENAI,
        self::DRIVER_ANTHROPIC,
        self::DRIVER_OPENAI_COMPATIBLE,
    ];

    protected $fillable = [
        'name',
        'driver',
        'api_key',
        'base_url',
        'model',
        'is_active',
        'is_default_content',
        'is_default_assistant',
        'sort_order',
        'meta',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => SafeEncryptedString::class,
            'is_active' => 'boolean',
            'is_default_content' => 'boolean',
            'is_default_assistant' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function driverLabels(): array
    {
        return [
            self::DRIVER_GEMINI => 'Google Gemini',
            self::DRIVER_OPENAI => 'OpenAI',
            self::DRIVER_ANTHROPIC => 'Anthropic Claude',
            self::DRIVER_OPENAI_COMPATIBLE => 'OpenAI-Compatible (OpenRouter, Groq, DeepSeek, etc.)',
        ];
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->meta ?? [], $key, $default);
    }

    public function maskedApiKey(): ?string
    {
        $key = (string) ($this->api_key ?? '');
        if ($key === '') {
            return null;
        }

        $len = strlen($key);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }

        return Str::substr($key, 0, 4).str_repeat('*', max(4, $len - 8)).Str::substr($key, -4);
    }

    public function resolvedBaseUrl(): string
    {
        $custom = trim((string) ($this->base_url ?? ''));
        if ($custom !== '') {
            return rtrim($custom, '/');
        }

        return match ($this->driver) {
            self::DRIVER_OPENAI => 'https://api.openai.com/v1',
            self::DRIVER_ANTHROPIC => 'https://api.anthropic.com/v1',
            self::DRIVER_GEMINI => 'https://generativelanguage.googleapis.com/v1beta',
            default => '',
        };
    }

    public function resolvedModel(): string
    {
        $model = trim((string) ($this->model ?? ''));
        if ($model !== '') {
            return $model;
        }

        return match ($this->driver) {
            self::DRIVER_GEMINI => 'gemini-3.5-flash-lite',
            self::DRIVER_OPENAI, self::DRIVER_OPENAI_COMPATIBLE => 'gpt-4o-mini',
            self::DRIVER_ANTHROPIC => 'claude-3-5-haiku-latest',
            default => 'gpt-4o-mini',
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
