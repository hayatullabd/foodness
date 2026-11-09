<?php

namespace App\Services\Courier;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HoorinFraudProvider implements CourierFraudProviderInterface
{
    private const ENDPOINT = 'https://plugin.hoorin.com/courier/api/v1/search';

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     score: float|null,
     *     report: array<string, mixed>|null,
     *     no_record: bool
     * }
     */
    public function check(string $apiKey, string $phone): array
    {
        $normalizedPhone = $this->normalizePhone($phone);

        try {
            $response = Http::acceptJson()
                ->connectTimeout(2)
                ->timeout(3)
                ->get(self::ENDPOINT, [
                    'apiKey' => $apiKey,
                    'searchTerm' => $normalizedPhone,
                    'view' => 'full',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Hoorin courier ratio check connection failed.', [
                'phone' => $this->maskPhone($normalizedPhone),
                'error' => $exception->getMessage(),
            ]);

            return $this->failure('Could not reach the Hoorin Courier API. Please try again shortly.');
        } catch (Throwable $exception) {
            Log::error('Hoorin courier ratio check failed unexpectedly.', [
                'phone' => $this->maskPhone($normalizedPhone),
                'error' => $exception->getMessage(),
            ]);

            return $this->failure('Courier ratio check failed due to an unexpected error.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        if ($response->status() === 401 || ($payload['error'] ?? null) === 'Invalid Key') {
            return $this->failure('Hoorin API key is invalid. Please check your credentials in settings.', $payload !== [] ? $payload : null);
        }

        if ($response->status() === 400 || ($payload['error'] ?? null) === 'Missing Params') {
            return $this->failure('Hoorin API parameter error: '.($payload['error'] ?? 'Missing parameters'), $payload !== [] ? $payload : null);
        }

        if (($payload['success'] ?? false) === true) {
            return $this->processSuccessResponse($payload);
        }

        $errorMsg = is_string($payload['error'] ?? null) && $payload['error'] !== ''
            ? $payload['error']
            : (is_string($payload['message'] ?? null) ? $payload['message'] : 'Hoorin Courier API returned an error.');

        Log::warning('Hoorin courier ratio check API error.', [
            'phone' => $this->maskPhone($normalizedPhone),
            'http_status' => $response->status(),
            'error' => $errorMsg,
        ]);

        return $this->failure($errorMsg, $payload !== [] ? $payload : null);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     ok: bool,
     *     message: string,
     *     score: float|null,
     *     report: array<string, mixed>|null,
     *     no_record: bool
     * }
     */
    private function processSuccessResponse(array $payload): array
    {
        $overall = is_array($payload['overall'] ?? null) ? $payload['overall'] : [];
        $couriers = is_array($payload['couriers'] ?? null) ? $payload['couriers'] : [];

        $totalParcels = (int) ($overall['verified_total_parcels'] ?? 0);
        $deliveredParcels = (int) ($overall['verified_delivered_parcels'] ?? 0);
        $cancelledParcels = (int) ($overall['verified_cancelled_parcels'] ?? 0);

        // Also check if any individual courier has data or delivery_rate
        $hasAnyCourierData = false;
        foreach ($couriers as $courierData) {
            if (! is_array($courierData)) {
                continue;
            }
            if (($courierData['status'] ?? null) === 'success' || ($courierData['total_parcels'] ?? 0) > 0 || ($courierData['delivery_rate'] ?? null) !== null) {
                $hasAnyCourierData = true;
                break;
            }
        }

        $rawScore = $overall['overall_score'] ?? null;
        $score = $rawScore !== null && is_numeric($rawScore) ? round((float) $rawScore, 2) : null;

        $compatibleReport = $this->formatAsCompatibleReport($payload, $totalParcels, $deliveredParcels, $cancelledParcels, $score);

        if ($totalParcels === 0 && ! $hasAnyCourierData && $score === null) {
            return [
                'ok' => true,
                'message' => 'No courier parcel history for this phone.',
                'score' => null,
                'report' => $compatibleReport,
                'no_record' => true,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Courier ratio check completed successfully.',
            'score' => $score,
            'report' => $compatibleReport,
            'no_record' => false,
        ];
    }

    /**
     * Maps Hoorin payload to standard report format expected by CourierRatioCards:
     * [
     *   'provider' => 'hoorin',
     *   'data' => [
     *      'summary' => [...],
     *      'steadfast' => [...],
     *      'pathao' => [...],
     *      ...
     *   ],
     *   'raw' => [...]
     * ]
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function formatAsCompatibleReport(
        array $payload,
        int $totalParcels,
        int $deliveredParcels,
        int $cancelledParcels,
        ?float $score
    ): array {
        $summary = [
            'total_parcel' => $totalParcels,
            'success_parcel' => $deliveredParcels,
            'cancelled_parcel' => $cancelledParcels,
            'success_ratio' => $score !== null ? $score.'%' : null,
        ];

        $couriersData = [];
        $rawCouriers = is_array($payload['couriers'] ?? null) ? $payload['couriers'] : [];

        foreach ($rawCouriers as $key => $courier) {
            if (! is_array($courier)) {
                continue;
            }

            $keyLower = strtolower((string) $key);
            $total = $courier['total_parcels'] ?? null;
            $success = $courier['delivered_parcels'] ?? null;
            $cancelled = $courier['cancelled_parcels'] ?? null;
            $deliveryRate = $courier['delivery_rate'] ?? null;

            $couriersData[$keyLower] = [
                'name' => str($keyLower)->title()->toString(),
                'logo' => null,
                'total_parcel' => $total,
                'success_parcel' => $success,
                'cancelled_parcel' => $cancelled,
                'success_ratio' => $deliveryRate !== null ? (string) $deliveryRate : null,
                'status' => $courier['status'] ?? null,
                'enabled' => $courier['enabled'] ?? true,
                'details' => $courier['details'] ?? [],
            ];
        }

        return [
            'status' => 'success',
            'provider' => 'hoorin',
            'data' => array_merge([
                'summary' => $summary,
            ], $couriersData),
            'raw' => $payload,
        ];
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

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            return substr($digits, 2);
        }

        return $digits;
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) <= 4) {
            return '****';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
