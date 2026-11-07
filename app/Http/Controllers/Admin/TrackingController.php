<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Models\Setting;
use App\Services\TrackingPixelService;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    /**
     * @var array<string, list<string>>
     */
    private const LEGACY_SETTING_KEYS = [
        ApiIntegration::PROVIDER_FACEBOOK_PIXEL => [
            'fb_pixel_id',
            'fb_pixel_enabled',
        ],
        ApiIntegration::PROVIDER_FACEBOOK_CAPI => [
            'fb_pixel_id',
            'fb_access_token',
            'fb_test_event_code',
            'fb_capi_enabled',
        ],
        ApiIntegration::PROVIDER_GOOGLE_TRACKING => [
            'gtm_container_id',
            'custom_gtm_server_url',
            'ga4_measurement_id',
            'ga4_api_secret',
            'gtm_enabled',
            'ga4_api_enabled',
        ],
        ApiIntegration::PROVIDER_TIKTOK_PIXEL => [
            'tiktok_pixel_id',
            'tiktok_access_token',
            'tiktok_test_event_code',
            'tiktok_pixel_enabled',
            'tiktok_api_enabled',
        ],
    ];

    /**
     * Credential → legacy settings key map for partial revokes.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const LEGACY_FIELD_MAP = [
        ApiIntegration::PROVIDER_FACEBOOK_PIXEL => [
            'pixel_id' => ['fb_pixel_id'],
        ],
        ApiIntegration::PROVIDER_FACEBOOK_CAPI => [
            'pixel_id' => ['fb_pixel_id'],
            'access_token' => ['fb_access_token'],
            'test_event_code' => ['fb_test_event_code'],
        ],
        ApiIntegration::PROVIDER_GOOGLE_TRACKING => [
            'gtm_container_id' => ['gtm_container_id'],
            'custom_gtm_server_url' => ['custom_gtm_server_url'],
            'ga4_measurement_id' => ['ga4_measurement_id'],
            'ga4_api_secret' => ['ga4_api_secret'],
            'google_ads_id' => [],
        ],
        ApiIntegration::PROVIDER_TIKTOK_PIXEL => [
            'pixel_id' => ['tiktok_pixel_id'],
            'access_token' => ['tiktok_access_token'],
            'test_event_code' => ['tiktok_test_event_code'],
        ],
    ];

    public function editFacebook(): View
    {
        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_FACEBOOK_PIXEL);

        return view('admin.tracking.facebook', compact('integration'));
    }

    public function updateFacebook(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pixel_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_FACEBOOK_PIXEL);
        $integration->mergeCredentials([
            'pixel_id' => $validated['pixel_id'] ?? '',
        ]);
        $integration->is_active = $request->boolean('is_active');
        $integration->save();

        return redirect()
            ->route('admin.tracking.facebook.edit')
            ->with('success', 'Facebook Pixel settings saved successfully.');
    }

    public function editFacebookCapi(): View
    {
        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_FACEBOOK_CAPI);

        return view('admin.tracking.facebook-capi', [
            'integration' => $integration,
            'maskedAccessToken' => $integration->maskedCredential('access_token'),
        ]);
    }

    public function updateFacebookCapi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pixel_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string', 'max:5000'],
            'test_event_code' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_FACEBOOK_CAPI);
        $integration->mergeCredentials([
            'pixel_id' => $validated['pixel_id'] ?? '',
            'access_token' => $validated['access_token'] ?? '',
            'test_event_code' => $validated['test_event_code'] ?? '',
        ], $integration->secretKeys());
        $integration->is_active = $request->boolean('is_active');
        $integration->save();

        return redirect()
            ->route('admin.tracking.facebook-capi.edit')
            ->with('success', 'Facebook CAPI settings saved successfully.');
    }

    public function editGoogle(): View
    {
        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_GOOGLE_TRACKING);

        return view('admin.tracking.google-tag-manager', [
            'integration' => $integration,
            'maskedApiSecret' => $integration->maskedCredential('ga4_api_secret'),
        ]);
    }

    public function updateGoogle(Request $request, TrackingPixelService $tracking): RedirectResponse
    {
        $validated = $request->validate([
            'gtm_container_id' => ['nullable', 'string', 'max:64', 'regex:/^(GTM-)?[A-Za-z0-9]+$/'],
            'custom_gtm_server_url' => ['nullable', 'string', 'max:255'],
            'ga4_measurement_id' => ['nullable', 'string', 'max:64'],
            'ga4_api_secret' => ['nullable', 'string', 'max:255'],
            'google_ads_id' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $gtmId = $tracking->sanitizeGtmId((string) ($validated['gtm_container_id'] ?? ''));

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_GOOGLE_TRACKING);
        $integration->mergeCredentials([
            'gtm_container_id' => $gtmId ?? '',
            'custom_gtm_server_url' => trim((string) ($validated['custom_gtm_server_url'] ?? '')),
            'ga4_measurement_id' => $tracking->sanitizeId((string) ($validated['ga4_measurement_id'] ?? '')) ?? '',
            'ga4_api_secret' => $validated['ga4_api_secret'] ?? '',
            'google_ads_id' => $tracking->sanitizeId((string) ($validated['google_ads_id'] ?? '')) ?? '',
        ], $integration->secretKeys());
        $integration->is_active = $request->boolean('is_active');
        $integration->save();

        return redirect()
            ->route('admin.tracking.google.edit')
            ->with('success', 'Google Tag Manager settings saved successfully.');
    }

    public function editTiktok(): View
    {
        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_TIKTOK_PIXEL);

        return view('admin.tracking.tiktok', [
            'integration' => $integration,
            'maskedAccessToken' => $integration->maskedCredential('access_token'),
        ]);
    }

    public function updateTiktok(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pixel_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string', 'max:5000'],
            'test_event_code' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_TIKTOK_PIXEL);
        $integration->mergeCredentials([
            'pixel_id' => $validated['pixel_id'] ?? '',
            'access_token' => $validated['access_token'] ?? '',
            'test_event_code' => $validated['test_event_code'] ?? '',
        ], $integration->secretKeys());
        $integration->is_active = $request->boolean('is_active');
        $integration->save();

        return redirect()
            ->route('admin.tracking.tiktok.edit')
            ->with('success', 'TikTok Pixel & Events API settings saved successfully.');
    }

    public function revokeFacebook(Request $request): RedirectResponse
    {
        return $this->revokeProvider(
            $request,
            ApiIntegration::PROVIDER_FACEBOOK_PIXEL,
            'admin.tracking.facebook.edit',
            ['pixel_id'],
            'Facebook Pixel code revoked successfully.'
        );
    }

    public function revokeFacebookCapi(Request $request): RedirectResponse
    {
        return $this->revokeProvider(
            $request,
            ApiIntegration::PROVIDER_FACEBOOK_CAPI,
            'admin.tracking.facebook-capi.edit',
            ['pixel_id', 'access_token', 'test_event_code'],
            'Facebook CAPI credentials revoked successfully.'
        );
    }

    public function revokeGoogle(Request $request): RedirectResponse
    {
        return $this->revokeProvider(
            $request,
            ApiIntegration::PROVIDER_GOOGLE_TRACKING,
            'admin.tracking.google.edit',
            ['gtm_container_id', 'custom_gtm_server_url', 'ga4_measurement_id', 'ga4_api_secret', 'google_ads_id'],
            'Google tracking codes revoked successfully.'
        );
    }

    public function revokeTiktok(Request $request): RedirectResponse
    {
        return $this->revokeProvider(
            $request,
            ApiIntegration::PROVIDER_TIKTOK_PIXEL,
            'admin.tracking.tiktok.edit',
            ['pixel_id', 'access_token', 'test_event_code'],
            'TikTok tracking credentials revoked successfully.'
        );
    }

    /**
     * @param  list<string>  $allowedFields
     */
    private function revokeProvider(
        Request $request,
        string $provider,
        string $redirectRoute,
        array $allowedFields,
        string $successMessage
    ): RedirectResponse {
        $validated = $request->validate([
            'scope' => ['nullable', 'in:all,fields'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string'],
            'deactivate' => ['nullable', 'boolean'],
        ]);

        $scope = $validated['scope'] ?? 'fields';
        $fields = array_values(array_intersect($allowedFields, $validated['fields'] ?? []));
        $integration = ApiIntegration::forProvider($provider);

        if ($scope === 'all') {
            $integration->revokeAll();
            $integration->save();
            $this->clearLegacySettings(self::LEGACY_SETTING_KEYS[$provider] ?? []);

            return redirect()
                ->route($redirectRoute)
                ->with('success', $successMessage);
        }

        if ($fields === []) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', 'No credential selected to revoke.');
        }

        $integration->forgetCredentials($fields);

        if ($request->boolean('deactivate') || $this->shouldAutoDeactivate($integration, $provider)) {
            $integration->is_active = false;
        }

        $integration->save();

        $legacyKeys = [];
        foreach ($fields as $field) {
            foreach (self::LEGACY_FIELD_MAP[$provider][$field] ?? [] as $legacyKey) {
                $legacyKeys[] = $legacyKey;
            }
        }
        $this->clearLegacySettings(array_values(array_unique($legacyKeys)));

        $label = implode(', ', $fields);

        return redirect()
            ->route($redirectRoute)
            ->with('success', "Revoked: {$label}.");
    }

    private function shouldAutoDeactivate(ApiIntegration $integration, string $provider): bool
    {
        return match ($provider) {
            ApiIntegration::PROVIDER_FACEBOOK_PIXEL => blank($integration->credential('pixel_id')),
            ApiIntegration::PROVIDER_FACEBOOK_CAPI => blank($integration->credential('pixel_id'))
                || blank($integration->credential('access_token')),
            ApiIntegration::PROVIDER_GOOGLE_TRACKING => blank($integration->credential('gtm_container_id'))
                && blank($integration->credential('ga4_measurement_id')),
            ApiIntegration::PROVIDER_TIKTOK_PIXEL => blank($integration->credential('pixel_id')),
            default => false,
        };
    }

    /**
     * @param  list<string>  $keys
     */
    private function clearLegacySettings(array $keys): void
    {
        foreach ($keys as $key) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => '']
            );
        }

        if ($keys !== []) {
            StoreSettings::forget();
        }
    }
}
