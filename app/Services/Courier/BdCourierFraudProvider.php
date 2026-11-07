<?php

namespace App\Services\Courier;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BdCourierFraudProvider implements CourierFraudProviderInterface
{
    private const ENDPOINT = 'https://api.bdcourier.com/courier-check';

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
        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(2)
                ->timeout(3)
                ->post(self::ENDPOINT, [
                    'phone' => $phone,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('BD Courier fraud check connection failed.', [
                'phone' => $this->maskPhone($phone),
                'error' => $exception->getMessage(),
            ]);

            return $this->failure('Could not reach the Courier Ratio API. Please try again shortly.');
        } catch (Throwable $exception) {
            Log::error('BD Courier fraud check failed unexpectedly.', [
                'phone' => $this->maskPhone($phone),
                'error' => $exception->getMessage(),
            ]);

            return $this->failure('Courier ratio check failed due to an unexpected error.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        if (($payload['status'] ?? null) === 'success' && is_array($payload['data'] ?? null)) {
            $summary = is_array($payload['data']['summary'] ?? null)
                ? $payload['data']['summary']
                : [];

            if ($this->summaryHasNoParcels($summary)) {
                return [
                    'ok' => true,
                    'message' => 'No courier parcel history for this phone.',
                    'score' => null,
                    'report' => $payload,
                    'no_record' => true,
                ];
            }

            $score = $this->extractSuccessRatio($summary['success_ratio'] ?? null);

            return [
                'ok' => true,
                'message' => 'Courier ratio check completed successfully.',
                'score' => $score,
                'report' => $payload,
                'no_record' => false,
            ];
        }

        $message = is_string($payload['message'] ?? null) && $payload['message'] !== ''
            ? $payload['message']
            : 'Courier Ratio API returned an error.';

        if ($this->isPhoneNotFoundMessage($message)) {
            return [
                'ok' => false,
                'message' => $message,
                'score' => null,
                'report' => $payload !== [] ? $payload : null,
                'no_record' => true,
            ];
        }

        Log::warning('BD Courier fraud check API error.', [
            'phone' => $this->maskPhone($phone),
            'http_status' => $response->status(),
            'api_status' => $payload['status'] ?? null,
            'message' => $message,
        ]);

        return $this->failure($message, $payload !== [] ? $payload : null);
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

    /**
     * @param  array<string, mixed>  $summary
     */
    private function summaryHasNoParcels(array $summary): bool
    {
        if ($summary === []) {
            return true;
        }

        $total = $summary['total_parcel'] ?? null;

        if ($total === null && ($summary['success_ratio'] ?? null) === null) {
            return true;
        }

        if (is_numeric($total) && (int) $total === 0) {
            return true;
        }

        return false;
    }

    private function isPhoneNotFoundMessage(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'phone number not found')
            || str_contains($normalized, 'phone not found');
    }

    private function extractSuccessRatio(mixed $ratio): ?float
    {
        if ($ratio === null || $ratio === '') {
            return null;
        }

        if (is_string($ratio)) {
            $ratio = rtrim(trim($ratio), '%');
        }

        if (! is_numeric($ratio)) {
            return null;
        }

        return round((float) $ratio, 2);
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
