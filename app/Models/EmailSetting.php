<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

#[Fillable([
    'mailer',
    'host',
    'port',
    'encryption',
    'username',
    'password',
    'from_address',
    'from_name',
    'is_active',
    'admin_emails',
    'triggers',
    'templates',
])]
#[Hidden(['password'])]
class EmailSetting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_active' => 'boolean',
            'triggers' => 'array',
            'templates' => 'array',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'mailer' => 'smtp',
            'host' => null,
            'port' => 587,
            'encryption' => 'tls',
            'username' => null,
            'password' => null,
            'from_address' => null,
            'from_name' => null,
        ]);
    }

    public function isConfigured(): bool
    {
        return filled($this->host)
            && filled($this->from_address)
            && filled($this->mailer);
    }

    public function trigger(string $key, bool $default = false): bool
    {
        return (bool) data_get($this->triggers ?? [], $key, $default);
    }

    /**
     * @param  array{subject?: string, body?: string}|null  $default
     * @return array{subject: string, body: string}|null
     */
    public function template(string $key, ?array $default = null): ?array
    {
        return data_get($this->templates ?? [], $key, $default);
    }

    public function mailScheme(): string
    {
        return match ($this->encryption) {
            'ssl' => 'smtps',
            default => 'smtp',
        };
    }

    public function applyToMailConfig(): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        Config::set('mail.default', $this->mailer);

        Config::set('mail.mailers.smtp', array_merge(
            (array) Config::get('mail.mailers.smtp', []),
            [
                'transport' => 'smtp',
                'host' => $this->host,
                'port' => $this->port,
                'username' => $this->username,
                'password' => $this->password,
                'scheme' => $this->mailScheme(),
            ],
        ));

        Config::set('mail.from.address', $this->from_address);
        Config::set('mail.from.name', $this->from_name ?: Config::get('app.name'));
    }

    public static function applyConfiguredMail(): void
    {
        static::current()->applyToMailConfig();
    }
}
