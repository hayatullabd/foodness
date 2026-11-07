<?php

namespace App\Services;

use App\Models\FraudSetting;
use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Services\Courier\BdCourierFraudProvider;
use App\Services\Courier\CourierFraudProviderInterface;
use App\Services\Courier\HoorinFraudProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BdCourierFraudService
{
    private const CHECK_CACHE_SECONDS = 90;

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     score: float|null,
     *     report: array<string, mixed>|null,
     *     no_record: bool
     * }
     */
    public function applyToOrder(Order $order, bool $softFail = false): array
    {
        $result = $this->check((string) $order->customer_phone);

        if ($result['ok']) {
            $order->update([
                'fraud_score' => $result['score'],
                'fraud_report' => $result['report'],
            ]);
        } elseif ($softFail) {
            Log::info('Fraud check skipped or failed without blocking.', [
                'order_id' => $order->id,
                'message' => $result['message'],
            ]);
        }

        return $result;
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     score: float|null,
     *     report: array<string, mixed>|null,
     *     no_record: bool
     * }
     */
    public function applyToIncompleteOrder(IncompleteOrder $incompleteOrder, bool $softFail = false): array
    {
        $result = $this->check((string) $incompleteOrder->customer_phone);

        if ($result['ok']) {
            $incompleteOrder->update([
                'fraud_score' => $result['score'],
                'fraud_report' => $result['report'],
            ]);
        } elseif ($softFail) {
            Log::info('Incomplete order fraud check skipped or failed without blocking.', [
                'incomplete_order_id' => $incompleteOrder->id,
                'message' => $result['message'],
            ]);
        }

        return $result;
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     score: float|null,
     *     report: array<string, mixed>|null,
     *     no_record: bool
     * }
     */
    public function check(string $phone): array
    {
        $settings = FraudSetting::current();

        if (! $settings->is_active) {
            return $this->failure('Courier Ratio API is disabled. Enable it under Courier Ratio Settings.');
        }

        $providerName = $settings->activeProvider();
        $apiKey = $settings->keyForProvider($providerName);

        if (blank($apiKey)) {
            $providerLabel = $providerName === FraudSetting::PROVIDER_HOORIN ? 'Hoorin' : 'BD Courier';

            return $this->failure("Courier Ratio API key is missing. Add your {$providerLabel} API key under Manage Courier Ratio API.");
        }

        $phone = trim($phone);

        if ($phone === '') {
            return $this->failure('Customer phone number is missing for this order.');
        }

        $cacheKey = "courier-fraud-check:{$providerName}:".sha1($phone);

        /** @var array{ok: bool, message: string, score: float|null, report: array<string, mixed>|null, no_record: bool}|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && ($cached['ok'] ?? false) === true) {
            return $cached;
        }

        $provider = $this->resolveProvider($providerName);
        $result = $provider->check((string) $apiKey, $phone);

        // Only cache successful lookups so failed checks can be retried immediately.
        if (($result['ok'] ?? false) === true) {
            Cache::put($cacheKey, $result, self::CHECK_CACHE_SECONDS);
        }

        return $result;
    }

    public function resolveProvider(string $providerName): CourierFraudProviderInterface
    {
        return match ($providerName) {
            FraudSetting::PROVIDER_HOORIN => app(HoorinFraudProvider::class),
            default => app(BdCourierFraudProvider::class),
        };
    }

    /**
     * @param  array<string, mixed>|null  $report
     * @return array{
     *     ok: bool,
     *     message: string,
     *     score: float|null,
     *     report: array<string, mixed>|null,
     *     no_record: bool
     * }
     */
    private function failure(string $message, ?array $report = null): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'score' => null,
            'report' => $report,
            'no_record' => false,
        ];
    }
}
