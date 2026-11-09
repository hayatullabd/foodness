<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CronJobSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class CronJobController extends Controller
{
    public function index(): View
    {
        $job = CronJobSetting::courierStatusSync();
        $jobs = CronJobSetting::query()->orderBy('id')->get();
        $activeCount = $jobs->where('is_enabled', true)->count();
        $cronCommand = '* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1';

        return view('admin.cron-jobs.index', [
            'job' => $job,
            'jobs' => $jobs,
            'activeCount' => $activeCount,
            'totalJobs' => $jobs->count(),
            'cronCommand' => $cronCommand,
            'frequencyOptions' => [5, 10, 15, 30, 60],
            'limitOptions' => [25, 50, 100, 200],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'frequency_minutes' => ['required', 'integer', 'in:5,10,15,30,60'],
            'order_limit' => ['required', 'integer', 'in:25,50,100,200'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $job = CronJobSetting::courierStatusSync();

        $job->update([
            'frequency_minutes' => (int) $validated['frequency_minutes'],
            'order_limit' => (int) $validated['order_limit'],
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return redirect()
            ->route('admin.cron-jobs.index')
            ->with('success', 'Cron settings saved successfully.');
    }

    public function toggle(): RedirectResponse
    {
        $job = CronJobSetting::courierStatusSync();
        $job->update([
            'is_enabled' => ! $job->is_enabled,
        ]);

        $state = $job->is_enabled ? 'enabled' : 'disabled';

        return redirect()
            ->route('admin.cron-jobs.index')
            ->with('success', "Courier Status Sync is now {$state}.");
    }

    public function runNow(): RedirectResponse
    {
        $job = CronJobSetting::courierStatusSync();

        if ($job->last_run_status === 'running') {
            return redirect()
                ->route('admin.cron-jobs.index')
                ->with('error', 'Sync is already running. Please wait a moment.');
        }

        $limit = $job->order_limit ?: 50;

        Artisan::call('courier:check-status', [
            '--limit' => $limit,
            '--force' => true,
        ]);

        return redirect()
            ->route('admin.cron-jobs.index')
            ->with('success', 'Courier status sync finished. '.trim(Artisan::output()));
    }
}
