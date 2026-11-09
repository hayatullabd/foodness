<?php

namespace App\Support;

class BackgroundQueueRunner
{
    /**
     * Trigger an asynchronous background worker execution to process pending jobs
     * without blocking the current HTTP response.
     */
    public static function trigger(): void
    {
        if (config('queue.default') === 'sync') {
            return;
        }

        try {
            $php = PHP_BINARY ?: 'php';
            $artisan = base_path('artisan');

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen("start /B \"\" \"{$php}\" \"{$artisan}\" queue:work --stop-when-empty --tries=2 --timeout=25 > NUL 2>&1", 'r'));
            } else {
                exec("\"{$php}\" \"{$artisan}\" queue:work --stop-when-empty --tries=2 --timeout=25 > /dev/null 2>&1 &");
            }
        } catch (\Throwable) {
            // Fails silently; dedicated supervisor / cron workers will process jobs
        }
    }
}
