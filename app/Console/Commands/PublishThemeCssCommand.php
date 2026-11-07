<?php

namespace App\Console\Commands;

use App\Support\ThemeCssPublisher;
use Illuminate\Console\Command;

class PublishThemeCssCommand extends Command
{
    protected $signature = 'app:publish-theme-css';

    protected $description = 'Publish foodness-theme.css to public/css (works without npm/Vite)';

    public function handle(ThemeCssPublisher $publisher): int
    {
        $result = $publisher->publish();

        if (! $result['ok']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info($result['message']);
        if (! empty($result['path'])) {
            $this->line('Path: '.$result['path']);
        }

        return self::SUCCESS;
    }
}
