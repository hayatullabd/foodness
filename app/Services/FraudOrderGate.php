<?php

namespace App\Services;

use App\Models\FraudOrderRuleSetting;
use App\Models\FraudSetting;
use Illuminate\Support\Facades\Log;

class FraudOrderGate
{
    public const BLOCK_MESSAGE = 'Unable to place order due to delivery history. Please contact support.';

    public function __construct(private BdCourierFraudService $fraudService) {}

    public function settings(): FraudOrderRuleSetting
    {
        return FraudOrderRuleSetting::current();
    }

    public function isEnabled(): bool
    {
        return $this->settings()->enabled;
    }

    /**
     * Returns a customer-facing block message, or null when the order may proceed.
     */
    public function blockingMessage(?string $customerPhone): ?string
    {
        $settings = $this->settings();

        if (! $settings->enabled) {
            return null;
        }

        if (! FraudSetting::current()->isConfigured()) {
            return null;
        }

        $phone = trim((string) $customerPhone);

        if ($phone === '') {
            return null;
        }

        $result = $this->fraudService->check($phone);

        if (($result['no_record'] ?? false) === true) {
            return null;
        }

        if (! ($result['ok'] ?? false)) {
            Log::info('Fraud order gate soft-allowed checkout after Fraud API failure.', [
                'phone' => $this->maskPhone($phone),
                'message' => $result['message'] ?? null,
            ]);

            return null;
        }

        $score = $result['score'] ?? null;

        if ($score === null) {
            Log::info('Fraud order gate soft-allowed checkout with missing success ratio.', [
                'phone' => $this->maskPhone($phone),
            ]);

            return null;
        }

        $threshold = max(0, min(100, (int) $settings->min_success_ratio));

        if ((float) $score <= $threshold) {
            return self::BLOCK_MESSAGE;
        }

        return null;
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
