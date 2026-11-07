<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'job_key',
    'job_title',
    'job_description',
    'is_enabled',
    'frequency_minutes',
    'order_limit',
    'last_run_at',
    'last_run_status',
    'last_run_result',
    'last_updated_count',
    'last_failed_count',
])]
class CronJobSetting extends Model
{
    public const KEY_COURIER_STATUS_SYNC = 'courier_status_sync';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'frequency_minutes' => 'integer',
            'order_limit' => 'integer',
            'last_run_at' => 'datetime',
            'last_updated_count' => 'integer',
            'last_failed_count' => 'integer',
        ];
    }

    public static function forKey(string $key): ?self
    {
        return static::query()->where('job_key', $key)->first();
    }

    public static function courierStatusSync(): self
    {
        return static::query()->firstOrCreate(
            ['job_key' => self::KEY_COURIER_STATUS_SYNC],
            [
                'job_title' => 'Courier Status Sync',
                'job_description' => 'Polls Pathao, Steadfast & RedX for in-courier / on-the-way / processing orders and updates status (webhooks also update instantly where configured). Enabled by default.',
                'is_enabled' => true,
                'frequency_minutes' => 10,
                'order_limit' => 50,
                'last_updated_count' => 0,
                'last_failed_count' => 0,
            ],
        );
    }

    public function frequencyLabel(): string
    {
        $minutes = (int) $this->frequency_minutes;

        if ($minutes >= 60 && $minutes % 60 === 0) {
            $hours = $minutes / 60;

            return $hours === 1 ? '1 hour' : "{$hours} hours";
        }

        return $minutes === 1 ? '1 minute' : "{$minutes} minutes";
    }
}
