<?php

namespace App\Console\Commands;

use App\Services\Update\SystemUpdateService;
use Illuminate\Console\Command;

class CheckSystemAutoUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:auto-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for new GitHub Releases or server updates and automatically upgrade the store';

    public function handle(SystemUpdateService $updater): int
    {
        if (! config('version.auto_update_enabled')) {
            $this->info('Automatic background updates are disabled (AUTO_UPDATE_ENABLED=false).');

            return self::SUCCESS;
        }

        $this->info('Checking for software updates...');
        $result = $updater->checkAndApplyAutoUpdate();

        if ($result['ok']) {
            $this->info('System auto-updated successfully: '.$result['message']);
        } else {
            $this->line('Auto-update status: '.$result['message']);
        }

        return self::SUCCESS;
    }
}
