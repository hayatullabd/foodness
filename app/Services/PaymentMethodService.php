<?php

namespace App\Services;

use App\Models\ApiIntegration;
use Illuminate\Support\Facades\Cache;

class PaymentMethodService
{
    protected const CACHE_KEY = 'payment_methods_gateways_data';

    protected const CACHE_TTL = 3600; // 1 hour (cleared on admin update)

    public function __construct(
        protected SslCommerzService $sslCommerz,
    ) {}

    /**
     * Clear cached payment integrations.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get all relevant payment integrations in a single cached query.
     *
     * @return array<string, array{is_active: bool, credentials: array<string, mixed>}>
     */
    protected function cachedIntegrations(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $records = ApiIntegration::query()
                ->whereIn('provider', [
                    ApiIntegration::PROVIDER_COD,
                    ApiIntegration::PROVIDER_SSLCOMMERZ,
                    ApiIntegration::PROVIDER_MANUAL_PAYMENT,
                ])
                ->get()
                ->keyBy('provider');

            return [
                'cod' => [
                    'is_active' => (bool) ($records[ApiIntegration::PROVIDER_COD]?->is_active ?? true),
                    'credentials' => $records[ApiIntegration::PROVIDER_COD]?->credentials ?? [],
                ],
                'sslcommerz' => [
                    'is_active' => (bool) ($records[ApiIntegration::PROVIDER_SSLCOMMERZ]?->is_active ?? false),
                    'credentials' => $records[ApiIntegration::PROVIDER_SSLCOMMERZ]?->credentials ?? [],
                ],
                'manual' => [
                    'is_active' => (bool) ($records[ApiIntegration::PROVIDER_MANUAL_PAYMENT]?->is_active ?? false),
                    'credentials' => $records[ApiIntegration::PROVIDER_MANUAL_PAYMENT]?->credentials ?? [],
                ],
            ];
        });
    }

    public function isCodEnabled(): bool
    {
        return (bool) ($this->cachedIntegrations()['cod']['is_active'] ?? true);
    }

    public function isSslCommerzEnabled(): bool
    {
        $sslData = $this->cachedIntegrations()['sslcommerz'];
        if (! ($sslData['is_active'] ?? false)) {
            return false;
        }

        $storeId = (string) (config('services.sslcommerz.store_id') ?: ($sslData['credentials']['store_id'] ?? ''));
        $storePassword = (string) (config('services.sslcommerz.store_password') ?: ($sslData['credentials']['store_password'] ?? ''));

        return filled($storeId) && filled($storePassword);
    }

    public function isManualPaymentEnabled(): bool
    {
        return (bool) ($this->cachedIntegrations()['manual']['is_active'] ?? false);
    }

    /**
     * Return list of active gateway IDs, e.g. ['cod', 'sslcommerz', 'manual'].
     * If all are disabled by mistake, fallback to 'cod'.
     *
     * @return list<string>
     */
    public function allowedMethods(): array
    {
        $allowed = [];

        if ($this->isCodEnabled()) {
            $allowed[] = 'cod';
        }

        if ($this->isSslCommerzEnabled()) {
            $allowed[] = 'sslcommerz';
        }

        if ($this->isManualPaymentEnabled()) {
            $allowed[] = 'manual';
        }

        if (empty($allowed)) {
            $allowed[] = 'cod';
        }

        return $allowed;
    }

    /**
     * Get structured array of active payment gateways for UI display.
     *
     * @return list<array{id: string, title: string, subtitle: string, is_default: bool, details: array<string, mixed>}>
     */
    public function availableGateways(): array
    {
        $gateways = [];
        $data = $this->cachedIntegrations();
        $codData = $data['cod'];
        $manualData = $data['manual'];

        if ($this->isCodEnabled()) {
            $gateways[] = [
                'id' => 'cod',
                'title' => (string) ($codData['credentials']['title'] ?? 'Cash on Delivery (COD)'),
                'subtitle' => (string) ($codData['credentials']['instructions'] ?? 'Pay in cash when you receive the parcel at your doorstep.'),
                'is_default' => false,
                'details' => [],
            ];
        }

        if ($this->isSslCommerzEnabled()) {
            $gateways[] = [
                'id' => 'sslcommerz',
                'title' => 'Pay Online (SSLCommerz)',
                'subtitle' => 'Pay securely with bKash, Nagad, Rocket, Visa or Mastercard.',
                'is_default' => false,
                'details' => [],
            ];
        }

        if ($this->isManualPaymentEnabled()) {
            $manualCreds = $manualData['credentials'];
            $manualTitle = (string) ($manualCreds['title'] ?? 'Manual Payment / Bank Transfer');
            $instructions = (string) ($manualCreds['instructions'] ?? '');
            $bank = (string) ($manualCreds['bank_name'] ?? '');
            $accName = (string) ($manualCreds['account_name'] ?? '');
            $accNo = (string) ($manualCreds['account_number'] ?? '');

            $subtitleParts = [];
            if ($instructions !== '') {
                $subtitleParts[] = $instructions;
            }
            $bankParts = [];
            if ($bank !== '') {
                $bankParts[] = "Bank: {$bank}";
            }
            if ($accName !== '') {
                $bankParts[] = "A/C Name: {$accName}";
            }
            if ($accNo !== '') {
                $bankParts[] = "A/C No: {$accNo}";
            }
            if (! empty($bankParts)) {
                $subtitleParts[] = implode(' | ', $bankParts);
            }

            $gateways[] = [
                'id' => 'manual',
                'title' => $manualTitle ?: 'Manual / Bank Transfer',
                'subtitle' => implode("\n", $subtitleParts),
                'is_default' => false,
                'details' => [
                    'bank_name' => $bank,
                    'account_name' => $accName,
                    'account_number' => $accNo,
                    'instructions' => $instructions,
                ],
            ];
        }

        // Fallback if none enabled
        if (empty($gateways)) {
            $gateways[] = [
                'id' => 'cod',
                'title' => 'Cash on Delivery (COD)',
                'subtitle' => 'Pay in cash when you receive the parcel at your doorstep.',
                'is_default' => true,
                'details' => [],
            ];
        } else {
            $gateways[0]['is_default'] = true;
        }

        return $gateways;
    }
}
