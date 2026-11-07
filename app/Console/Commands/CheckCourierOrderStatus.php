<?php

namespace App\Console\Commands;

use App\Models\CronJobSetting;
use App\Services\Courier\CourierStatusSyncService;
use Illuminate\Console\Command;
use Throwable;

class CheckCourierOrderStatus extends Command
{
    protected $signature = 'courier:check-status {--limit=50 : Maximum orders to check} {--force : Run even if disabled in admin}';

    protected $description = 'Check order status from Pathao, Steadfast, and RedX courier APIs and update orders automatically';

    public function handle(CourierStatusSyncService $syncService): int
    {
        $setting = CronJobSetting::courierStatusSync();

        if (! $setting->is_enabled && ! $this->option('force')) {
            $this->warn('Courier status sync is disabled in the admin panel. Use --force to override.');

            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');

        if ($limit === 50 && $setting->order_limit > 0) {
            $limit = (int) $setting->order_limit;
        }

        $setting->update([
            'last_run_at' => now(),
            'last_run_status' => 'running',
            'last_run_result' => 'Running…',
        ]);

        $this->info("Courier status sync started — limit: {$limit}");

        try {
            $result = $syncService->sync($limit);
        } catch (Throwable $e) {
            $setting->update([
                'last_run_status' => 'failed',
                'last_run_result' => $e->getMessage(),
                'last_updated_count' => 0,
                'last_failed_count' => 0,
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($result['checked'] === 0) {
            $message = 'No courier orders with tracking numbers to check (in_courier, on_the_way, processing).';
            $this->info($message);

            $setting->update([
                'last_run_status' => 'success',
                'last_run_result' => $message,
                'last_updated_count' => 0,
                'last_failed_count' => 0,
            ]);

            return self::SUCCESS;
        }

        $message = sprintf(
            'Checked: %d | Updated: %d | Unchanged: %d | Failed: %d',
            $result['checked'],
            $result['updated'],
            $result['unchanged'],
            $result['failed'],
        );

        $this->info($message);

        $setting->update([
            'last_run_status' => $result['failed'] > 0 && $result['updated'] === 0 ? 'failed' : 'success',
            'last_run_result' => $message,
            'last_updated_count' => $result['updated'],
            'last_failed_count' => $result['failed'],
        ]);

        return self::SUCCESS;
    }
}
