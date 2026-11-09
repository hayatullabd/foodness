<?php

namespace App\Services;

use App\Models\ApiIntegration;

class TrackingPixelService
{
    public function __construct(protected ?TrackingService $trackingService = null)
    {
        $this->trackingService = $trackingService ?? app(TrackingService::class);
    }

    /**
     * Resolved storefront tracking configuration.
     *
     * @return array{
     *     facebook: array{enabled: bool, pixel_id: string|null},
     *     google: array{enabled: bool, gtm_container_id: string|null, custom_server_url: string|null, ga4_measurement_id: string|null, google_ads_id: string|null, gtag_enabled: bool},
     *     tiktok: array{enabled: bool, pixel_id: string|null}
     * }
     */
    public function storefrontConfig(): array
    {
        $config = $this->trackingService->getConfig();

        $googleIntegration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_GOOGLE_TRACKING);
        $adsId = $this->sanitizeId((string) $googleIntegration->credential('google_ads_id', ''));

        return [
            'facebook' => [
                'enabled' => $config['meta']['pixel_enabled'],
                'pixel_id' => $config['meta']['pixel_id'],
            ],
            'google' => [
                'enabled' => $config['gtm']['enabled'],
                'gtm_container_id' => $config['gtm']['container_id'],
                'custom_server_url' => $config['gtm']['custom_server_url'],
                'ga4_measurement_id' => $config['ga4']['measurement_id'],
                'google_ads_id' => $adsId,
                'gtag_enabled' => $googleIntegration->is_active && (filled($config['ga4']['measurement_id']) || filled($adsId)),
            ],
            'tiktok' => [
                'enabled' => $config['tiktok']['pixel_enabled'],
                'pixel_id' => $config['tiktok']['pixel_id'],
            ],
        ];
    }

    public function sanitizeId(string $value): ?string
    {
        return $this->trackingService->sanitizeId($value);
    }

    public function sanitizeGtmId(string $value): ?string
    {
        return $this->trackingService->sanitizeGtmId($value);
    }
}
