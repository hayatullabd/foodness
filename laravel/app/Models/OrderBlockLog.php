<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'block_type',
    'offending_value',
    'reason',
    'ip_address',
    'phone',
    'device_id',
    'customer_name',
    'customer_address',
    'attempts_count',
    'last_attempt_at',
    'payload',
])]
class OrderBlockLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts_count' => 'integer',
            'last_attempt_at' => 'datetime',
        ];
    }

    /**
     * Delete log records older than the given number of days (default 30 days).
     */
    public static function pruneOlderThan(int $days = 30): int
    {
        return static::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Convenient factory to record or increment a blocked checkout attempt.
     * Groups repeated identical attempts into a single row with an incremented attempts count.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public static function record(
        string $blockType,
        string $reason,
        ?string $offendingValue = null,
        ?string $ipAddress = null,
        ?string $phone = null,
        ?string $deviceId = null,
        ?string $customerName = null,
        ?string $customerAddress = null,
        ?array $payload = null,
    ): self {
        // Auto-clean logs older than 30 days
        static::pruneOlderThan(30);

        // Find recent existing log matching this block signature within last 30 days
        $query = static::query()
            ->where('block_type', $blockType)
            ->where('created_at', '>=', now()->subDays(30));

        if ($phone !== null && $phone !== '') {
            $query->where('phone', $phone);
        } elseif ($deviceId !== null && $deviceId !== '') {
            $query->where('device_id', $deviceId);
        } elseif ($offendingValue !== null && $offendingValue !== '') {
            $query->where('offending_value', $offendingValue);
        } elseif ($ipAddress !== null && $ipAddress !== '') {
            $query->where('ip_address', $ipAddress);
        }

        $existing = $query->latest('id')->first();

        if ($existing !== null) {
            $existing->update([
                'attempts_count' => (int) ($existing->attempts_count ?? 1) + 1,
                'last_attempt_at' => now(),
                'reason' => $reason,
                'customer_name' => $customerName ?: $existing->customer_name,
                'customer_address' => $customerAddress ?: $existing->customer_address,
                'ip_address' => $ipAddress ?: $existing->ip_address,
                'phone' => $phone ?: $existing->phone,
                'device_id' => $deviceId ?: $existing->device_id,
                'payload' => $payload ?: $existing->payload,
            ]);

            return $existing;
        }

        return static::query()->create([
            'block_type' => $blockType,
            'reason' => $reason,
            'offending_value' => $offendingValue,
            'ip_address' => $ipAddress,
            'phone' => $phone,
            'device_id' => $deviceId,
            'customer_name' => $customerName,
            'customer_address' => $customerAddress,
            'attempts_count' => 1,
            'last_attempt_at' => now(),
            'payload' => $payload,
        ]);
    }

    /**
     * Human-readable label for the block type.
     */
    public function typeLabel(): string
    {
        return match ($this->block_type) {
            'ip_blacklist' => 'IP Blacklist',
            'ip_rate_limit' => 'IP Rate Limit',
            'phone_blacklist' => 'Phone Blacklist',
            'phone_rate_limit' => 'Phone Rate Limit',
            'invalid_phone' => 'Invalid Phone Format',
            'device_blacklist' => 'Device Blacklist',
            'device_rate_limit' => 'Device Rate Limit',
            'spam_content' => 'Spam Keyword',
            'short_address' => 'Incomplete Address',
            'product_limit' => 'Product Order Limit',
            default => ucfirst(str_replace('_', ' ', $this->block_type)),
        };
    }

    /**
     * Complete structured details array for inspection modals and audit drawers.
     *
     * @return array<string, mixed>
     */
    public function detailsArray(): array
    {
        $payload = is_array($this->payload) ? $this->payload : [];
        $device = $payload['device'] ?? [];
        $browser = $payload['browser'] ?? [];
        $network = $payload['network'] ?? [];
        $attempt = $payload['attempt'] ?? [];

        $os = trim(($browser['os'] ?? '').' '.($browser['os_version'] ?? ''));
        $browserName = trim(($browser['name'] ?? '').' '.($browser['browser_version'] ?? ''));

        return [
            'id' => $this->id,
            'block_type' => $this->block_type,
            'type_label' => $this->typeLabel(),
            'offending_value' => $this->offending_value,
            'reason' => $this->reason,
            'attempts_count' => (int) ($this->attempts_count ?? 1),
            'first_attempt_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'last_attempt_at' => ($this->last_attempt_at ?: $this->created_at)?->format('Y-m-d H:i:s'),
            'created_at_human' => ($this->last_attempt_at ?: $this->created_at)?->diffForHumans(),

            // Device & Hardware
            'device_id' => $this->device_id ?: ($device['token'] ?? null),
            'device_type' => $browser['device_type'] ?? 'Desktop',
            'os' => $os !== '' ? $os : 'Unknown OS',
            'browser' => $browserName !== '' ? $browserName : 'Unknown Browser',
            'screen' => $device['screen'] ?? '—',
            'color_depth' => $device['color_depth'] ?? null,
            'pixel_ratio' => $device['pixel_ratio'] ?? null,
            'gpu_vendor' => $device['gpu_vendor'] ?? null,
            'gpu_renderer' => $device['gpu_renderer'] ?? null,
            'cores' => $device['cores'] ?? null,
            'memory_gb' => $device['memory_gb'] ?? null,
            'touch_points' => $device['touch_points'] ?? null,
            'platform' => $device['platform'] ?? null,
            'timezone' => $device['timezone'] ?? null,
            'language' => $device['language'] ?? null,
            'audio_sig' => $device['audio_sig'] ?? null,
            'canvas_sig' => $device['canvas_sig'] ?? null,
            'user_agent' => $browser['user_agent'] ?? ($payload['user_agent'] ?? '—'),

            // Network & Geolocation
            'ip_address' => $this->ip_address ?: ($network['ip'] ?? '—'),
            'is_local' => (bool) ($network['is_local'] ?? false),
            'country' => $network['country'] ?? 'Bangladesh',
            'country_code' => $network['country_code'] ?? 'BD',
            'city' => $network['city'] ?? 'Dhaka',
            'region' => $network['region'] ?? 'Dhaka Division',
            'isp' => $network['isp'] ?? 'Broadband / Cellular',
            'org' => $network['org'] ?? 'Internet Provider',
            'forwarded_for' => $network['forwarded_for'] ?? null,
            'cf_ray' => $network['cf_ray'] ?? null,

            // Customer & Checkout Attempt
            'customer_name' => $this->customer_name ?: ($attempt['customer_name'] ?? 'Guest / Bot'),
            'phone' => $this->phone ?: ($attempt['customer_phone'] ?? '—'),
            'customer_address' => $this->customer_address ?: ($attempt['customer_address'] ?? '—'),
            'cart_items' => $attempt['cart_items'] ?? [],
            'items_count' => (int) ($attempt['items_count'] ?? 0),
            'total_amount' => (float) ($attempt['total_amount'] ?? 0.0),
            'url' => $attempt['url'] ?? null,
            'referer' => $attempt['referer'] ?? null,
            'method' => $attempt['method'] ?? 'POST',
        ];
    }
}
