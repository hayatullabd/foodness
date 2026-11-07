<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Category;
use App\Models\EmailSetting;
use App\Models\SeoSetting;
use App\Services\TrackingPixelService;
use App\Support\Cart;
use App\Support\StoreSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Cart::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction() && ! app()->runningUnitTests());
        Model::preventLazyLoading(! app()->isProduction());

        if (app()->isProduction()) {
            URL::forceScheme('https');
            DB::prohibitDestructiveCommands();
        }

        $this->configureRateLimiting();

        Gate::before(function ($user, string $ability): ?bool {
            if (! $user instanceof Admin) {
                return null;
            }

            if ($user->isSuperAdmin()) {
                return true;
            }

            // Accounts created before roles existed keep full access until a role is assigned.
            if ($user->roles()->doesntExist() && $user->permissions()->doesntExist()) {
                return true;
            }

            return null;
        });

        $customThemePath = resource_path('views/themes/custom');
        if (is_dir($customThemePath)) {
            View::prependLocation($customThemePath);
        }

        View::composer(['layouts.store', 'store.*', 'landing.*'], function ($view): void {
            $tracking = [
                'facebook' => ['enabled' => false, 'pixel_id' => null],
                'google' => [
                    'enabled' => false,
                    'gtm_container_id' => null,
                    'ga4_measurement_id' => null,
                    'google_ads_id' => null,
                    'gtag_enabled' => false,
                ],
                'tiktok' => ['enabled' => false, 'pixel_id' => null],
            ];

            $seo = [
                'site_title' => null,
                'meta_description' => null,
                'meta_keywords' => null,
                'google_site_verification' => null,
            ];

            $storeCategories = [];

            try {
                $tracking = app(TrackingPixelService::class)->storefrontConfig();
                $seo = SeoSetting::cached();
                $storeCategories = Cache::remember('store.categories.active', 3600, function (): array {
                    return Category::query()
                        ->active()
                        ->orderBy('sort_order')
                        ->get(['id', 'name', 'slug', 'image', 'status', 'is_front', 'sort_order'])
                        ->map(fn (Category $cat): array => [
                            'id' => $cat->id,
                            'name' => $cat->name,
                            'slug' => $cat->slug,
                            'image' => $cat->image,
                            'status' => (bool) $cat->status,
                            'is_front' => (bool) $cat->is_front,
                            'sort_order' => (int) $cat->sort_order,
                        ])
                        ->all();
                });
            } catch (Throwable) {
                // Database may be unavailable during install or early boot.
            }

            $view->with([
                'store' => StoreSettings::all(),
                'cartCount' => app(Cart::class)->count(),
                'tracking' => $tracking,
                'seo' => $seo,
                'storeCategories' => $storeCategories,
            ]);
        });

        $this->applyDatabaseMailSettings();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $identifier = (string) ($request->input('email') ?? $request->input('phone') ?? '');
            $throttleKey = Str::transliterate(Str::lower($identifier).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $identifier = (string) ($request->input('email') ?? $request->input('phone') ?? '');
            $throttleKey = Str::transliterate(Str::lower($identifier).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('public-api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }

    private function applyDatabaseMailSettings(): void
    {
        try {
            if (! Schema::hasTable('email_settings')) {
                return;
            }

            EmailSetting::applyConfiguredMail();
        } catch (Throwable) {
            // Database may be unavailable during install or early boot.
        }
    }
}
