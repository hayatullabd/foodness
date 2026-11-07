<?php

use App\Http\Middleware\BlockBlockedIp;
use App\Http\Middleware\CaptureTrackingCookiesMiddleware;
use App\Http\Middleware\CaptureTrafficAttribution;
use App\Http\Middleware\EnsureAdminSecurity;
use App\Http\Middleware\HandleAdminAjaxResponses;
use App\Http\Middleware\RedirectIfInstalled;
use App\Http\Middleware\RedirectIfNotInstalled;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\UseRealClientIp;
use App\Models\CronJobSetting;
use App\Models\OrderBlockLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

// Ensure critical framework directories exist to prevent "valid cache path" errors
$requiredDirs = [
    dirname(__DIR__).'/storage/framework/views',
    dirname(__DIR__).'/storage/framework/cache/data',
    dirname(__DIR__).'/storage/framework/sessions',
    dirname(__DIR__).'/storage/logs',
    __DIR__.'/cache',
];

foreach ($requiredDirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        if (! Schema::hasTable('cron_job_settings')) {
            return;
        }

        $setting = CronJobSetting::forKey(CronJobSetting::KEY_COURIER_STATUS_SYNC);

        if ($setting === null || ! $setting->is_enabled) {
            return;
        }

        $event = $schedule->command('courier:check-status')
            ->withoutOverlapping(10)
            ->name('courier-status-sync');

        match ((int) $setting->frequency_minutes) {
            5 => $event->everyFiveMinutes(),
            10 => $event->everyTenMinutes(),
            15 => $event->everyFifteenMinutes(),
            30 => $event->everyThirtyMinutes(),
            60 => $event->hourly(),
            default => $event->cron('*/'.max(1, (int) $setting->frequency_minutes).' * * * *'),
        };

        $schedule->command('system:auto-update')
            ->dailyAt('03:00')
            ->withoutOverlapping(30)
            ->name('system-auto-update');

        $schedule->call(function (): void {
            OrderBlockLog::pruneOlderThan(30);
        })
            ->dailyAt('04:00')
            ->name('prune-order-block-logs');

        $schedule->command('queue:prune-failed --hours=48')
            ->daily()
            ->name('prune-failed-queue-jobs');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(UseRealClientIp::class);
        $middleware->append(SecurityHeadersMiddleware::class);

        $middleware->alias([
            'admin' => EnsureAdminSecurity::class,
            'admin.ajax' => HandleAdminAjaxResponses::class,
            'block.ip' => BlockBlockedIp::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'installed' => RedirectIfInstalled::class,
            'not.installed' => RedirectIfNotInstalled::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payments/sslcommerz/success',
            'payments/sslcommerz/fail',
            'payments/sslcommerz/cancel',
            'payments/sslcommerz/ipn',
            'api/steadfast/webhook',
            'api/redx/webhook',
            'api/carrybee/webhook',
        ]);

        $middleware->encryptCookies(except: [
            '_fbp',
            '_fbc',
            '_ttp',
            '_ga',
            'ttclid',
            'foodness_did',
        ]);

        $middleware->web(append: [
            RedirectIfNotInstalled::class,
            CaptureTrafficAttribution::class,
            CaptureTrackingCookiesMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return Route::has('login') ? route('login') : '/login';
        });

        $middleware->redirectUsersTo(function (Request $request): string {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.dashboard');
            }

            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('admin/*')) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The requested item was not found or has already been deleted.',
                    ], 404);
                }

                return redirect()->route('admin.products.index')
                    ->with('error', 'The requested product was not found or has already been deleted.');
            }
        });
    })->create();
