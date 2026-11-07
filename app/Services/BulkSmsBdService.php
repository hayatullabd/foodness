<?php

namespace App\Services;

use App\Models\ApiIntegration;
use App\Models\Order;
use App\Support\StoreSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BulkSmsBdService
{
    public const SMS_API_URL = 'https://bulksmsbd.net/api/smsapi';

    public const BALANCE_API_URL = 'https://bulksmsbd.net/api/getBalanceApi';

    public const SUCCESS_RESPONSE_CODE = 202;

    public const TRIGGER_ORDER_SMS = 'order_sms';

    public const TRIGGER_ORDER_STATUS_SMS = 'order_status_sms';

    public const TRIGGER_FORGOT_PASSWORD_OTP = 'forgot_password_otp';

    public const TRIGGER_REGISTRATION_OTP = 'registration_otp';

    public const TRIGGER_ORDER_OTP = 'order_otp';

    public const TEMPLATE_CUSTOMER_ORDER = 'customer_order_template';

    public const TEMPLATE_ADMIN_ORDER = 'admin_order_template';

    public const TEMPLATE_ORDER_STATUS = 'order_status_template';

    public const TEMPLATE_REGISTRATION_OTP = 'registration_otp_template';

    public const TEMPLATE_FORGOT_PASSWORD_OTP = 'forgot_password_otp_template';

    public const TEMPLATE_ORDER_OTP = 'order_otp_template';

    public function integration(): ApiIntegration
    {
        return ApiIntegration::forProvider(ApiIntegration::PROVIDER_SMS_GATEWAY);
    }

    public function isEnabled(): bool
    {
        $integration = $this->integration();

        return $integration->is_active
            && filled($integration->credential('api_key'))
            && filled($integration->credential('sender_id'));
    }

    public function isTriggerEnabled(string $trigger): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return (bool) $this->integration()->credential($trigger, false);
    }

    /**
     * Normalize Bangladeshi mobile numbers to 8801XXXXXXXXX.
     */
    public function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '880') && strlen($digits) >= 13) {
            return substr($digits, 0, 13);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '88'.$digits;
        }

        if (str_starts_with($digits, '1') && strlen($digits) === 10) {
            return '880'.$digits;
        }

        return $digits;
    }

    /**
     * @return list<string>
     */
    public function adminNumbers(): array
    {
        $raw = (string) $this->integration()->credential('admin_numbers', '');

        if (blank($raw)) {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn (string $number): string => $this->normalizeNumber(trim($number)))
            ->filter(fn (string $number): bool => $number !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{ok: bool, message: string, response_code: int|null, balance: string|null, raw: mixed}
     */
    public function send(string $number, string $message): array
    {
        $integration = $this->integration();
        $apiKey = (string) $integration->credential('api_key', '');
        $senderId = (string) $integration->credential('sender_id', '');
        $normalized = $this->normalizeNumber($number);

        if (blank($apiKey) || blank($senderId)) {
            return [
                'ok' => false,
                'message' => 'SMS gateway API key or Sender ID is missing.',
                'response_code' => null,
                'balance' => null,
                'raw' => null,
            ];
        }

        if ($normalized === '') {
            return [
                'ok' => false,
                'message' => 'A valid phone number is required.',
                'response_code' => null,
                'balance' => null,
                'raw' => null,
            ];
        }

        $params = [
            'api_key' => $apiKey,
            'type' => 'text',
            'number' => $normalized,
            'senderid' => $senderId,
            'message' => $message,
        ];

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->acceptJson()
                ->get(self::SMS_API_URL, $params);

            return $this->interpretResponse($response->json() ?? $response->body(), 'SMS');
        } catch (ConnectionException $exception) {
            Log::warning('BulkSMSBD send connection failed.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Could not reach BulkSMSBD. Check network connectivity.',
                'response_code' => null,
                'balance' => null,
                'raw' => null,
            ];
        } catch (Throwable $exception) {
            Log::error('BulkSMSBD send failed.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'SMS send failed unexpectedly.',
                'response_code' => null,
                'balance' => null,
                'raw' => null,
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string, response_code: int|null, balance: string|null, raw: mixed}
     */
    public function getBalance(): array
    {
        $apiKey = (string) $this->integration()->credential('api_key', '');

        if (blank($apiKey)) {
            return [
                'ok' => false,
                'message' => 'Save an API key before checking balance.',
                'response_code' => null,
                'balance' => null,
                'raw' => null,
            ];
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->acceptJson()
                ->get(self::BALANCE_API_URL, [
                    'api_key' => $apiKey,
                ]);

            $interpreted = $this->interpretResponse($response->json() ?? $response->body(), 'Balance');

            if ($interpreted['ok'] && $interpreted['balance'] === null) {
                $payload = is_array($response->json()) ? $response->json() : [];
                $interpreted['balance'] = isset($payload['balance'])
                    ? (string) $payload['balance']
                    : (isset($payload['Balance']) ? (string) $payload['Balance'] : null);
            }

            return $interpreted;
        } catch (ConnectionException $exception) {
            Log::warning('BulkSMSBD balance connection failed.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Could not reach BulkSMSBD balance API.',
                'response_code' => null,
                'balance' => null,
                'raw' => null,
            ];
        } catch (Throwable $exception) {
            Log::error('BulkSMSBD balance check failed.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Balance check failed unexpectedly.',
                'response_code' => null,
                'balance' => null,
                'raw' => null,
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    public function defaultTemplates(): array
    {
        $siteName = (string) (StoreSettings::get('store_name') ?: config('app.name', 'Store'));

        return [
            self::TEMPLATE_CUSTOMER_ORDER => "{$siteName}: Your order {order_id} has been placed. Total: Tk {total}. Thank you!",
            self::TEMPLATE_ADMIN_ORDER => 'New order {order_id} from {customer_name} ({phone}). Total: Tk {total}.',
            self::TEMPLATE_ORDER_STATUS => "{$siteName}: Your order {order_id} status has been updated to {status}. Thank you!",
            self::TEMPLATE_REGISTRATION_OTP => "{$siteName} verification code: {otp}",
            self::TEMPLATE_FORGOT_PASSWORD_OTP => "{$siteName} verification code: {otp}",
            self::TEMPLATE_ORDER_OTP => "{$siteName}: Your order confirmation OTP is {otp}. Valid for 5 minutes.",
        ];
    }

    public function getTemplate(string $key): string
    {
        $custom = (string) $this->integration()->credential($key, '');

        if (trim($custom) !== '') {
            return trim($custom);
        }

        return $this->defaultTemplates()[$key] ?? '';
    }

    /**
     * Replace template placeholders with real values.
     *
     * @param  array<string, mixed>  $variables
     */
    public function renderTemplate(string $template, array $variables = []): string
    {
        $replacements = [];

        foreach ($variables as $key => $value) {
            $val = is_scalar($value) || $value === null ? (string) $value : '';
            $replacements['{'.$key.'}'] = $val;
            $replacements['{ '.$key.' }'] = $val;
        }

        return strtr($template, $replacements);
    }

    /**
     * Extract template variables from an Order.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, string>
     */
    public function orderVariables(Order $order, array $extra = []): array
    {
        $siteName = (string) (StoreSettings::get('store_name') ?: config('app.name', 'Store'));
        $invoice = (string) $order->invoice_no;
        $name = (string) $order->customer_name;
        $phone = $this->normalizeNumber((string) $order->customer_phone);
        $rawPhone = (string) $order->customer_phone;
        $total = number_format((float) $order->total, 2, '.', '');
        $status = (string) ($order->statusRelation?->name ?? ucfirst((string) $order->status));

        $defaults = [
            'order_id' => $invoice,
            'invoice_no' => $invoice,
            'customer_name' => $name,
            'name' => $name,
            'customer_phone' => $phone,
            'phone' => $phone,
            'raw_phone' => $rawPhone,
            'total' => $total,
            'amount' => $total,
            'status' => $status,
            'company_name' => $siteName,
            'store_name' => $siteName,
            'site_name' => $siteName,
            'tracking_number' => (string) ($order->tracking_number ?? ''),
        ];

        return array_merge($defaults, array_map('strval', $extra));
    }

    /**
     * Extract template variables for OTP.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, string>
     */
    public function otpVariables(string $otp, array $extra = []): array
    {
        $siteName = (string) (StoreSettings::get('store_name') ?: config('app.name', 'Store'));

        $defaults = [
            'otp' => $otp,
            'code' => $otp,
            'company_name' => $siteName,
            'store_name' => $siteName,
            'site_name' => $siteName,
        ];

        return array_merge($defaults, array_map('strval', $extra));
    }

    /**
     * Send customer confirmation and optional admin alerts when order SMS is enabled.
     */
    public function notifyOrderPlaced(Order $order): void
    {
        if (! $this->isTriggerEnabled(self::TRIGGER_ORDER_SMS)) {
            return;
        }

        $variables = $this->orderVariables($order);

        $customerMessage = $this->renderTemplate(
            $this->getTemplate(self::TEMPLATE_CUSTOMER_ORDER),
            $variables,
        );

        $customerResult = $this->send((string) $order->customer_phone, $customerMessage);

        if (! $customerResult['ok']) {
            Log::info('Order SMS to customer was not sent.', [
                'order_id' => $order->id,
                'message' => $customerResult['message'],
            ]);
        }

        $adminMessage = $this->renderTemplate(
            $this->getTemplate(self::TEMPLATE_ADMIN_ORDER),
            $variables,
        );

        foreach ($this->adminNumbers() as $adminNumber) {
            $adminResult = $this->send($adminNumber, $adminMessage);

            if (! $adminResult['ok']) {
                Log::info('Order SMS to admin was not sent.', [
                    'order_id' => $order->id,
                    'number' => $adminNumber,
                    'message' => $adminResult['message'],
                ]);
            }
        }
    }

    /**
     * Send order status update SMS to customer if enabled.
     *
     * @return array{ok: bool, message: string, response_code: int|null, balance: string|null, raw: mixed}|null
     */
    public function notifyOrderStatusUpdated(Order $order, ?string $statusName = null): ?array
    {
        if (! $this->isTriggerEnabled(self::TRIGGER_ORDER_STATUS_SMS)) {
            return null;
        }

        $variables = $this->orderVariables($order, [
            'status' => $statusName ?? (string) ($order->statusRelation?->name ?? ucfirst((string) $order->status)),
        ]);

        $message = $this->renderTemplate(
            $this->getTemplate(self::TEMPLATE_ORDER_STATUS),
            $variables,
        );

        $result = $this->send((string) $order->customer_phone, $message);

        if (! $result['ok']) {
            Log::info('Order status SMS was not sent.', [
                'order_id' => $order->id,
                'message' => $result['message'],
            ]);
        }

        return $result;
    }

    /**
     * TODO: Wire when CustomerAuth (or password reset) gains SMS OTP delivery.
     * Registration OTP / Forgot Password OTP toggles are stored but unused until those flows exist.
     */
    public function sendOtpIfEnabled(string $trigger, string $number, string $code): array
    {
        if (! $this->isTriggerEnabled($trigger)) {
            return [
                'ok' => false,
                'message' => 'OTP SMS trigger is disabled or gateway is off.',
                'response_code' => null,
                'balance' => null,
                'raw' => null,
            ];
        }

        $templateKey = match ($trigger) {
            self::TRIGGER_REGISTRATION_OTP => self::TEMPLATE_REGISTRATION_OTP,
            self::TRIGGER_FORGOT_PASSWORD_OTP => self::TEMPLATE_FORGOT_PASSWORD_OTP,
            self::TRIGGER_ORDER_OTP => self::TEMPLATE_ORDER_OTP,
            default => self::TEMPLATE_REGISTRATION_OTP,
        };

        $message = $this->renderTemplate(
            $this->getTemplate($templateKey),
            $this->otpVariables($code),
        );

        return $this->send($number, $message);
    }

    /**
     * @return array{api_key: string, type: string, number: string, senderid: string, message: string}
     */
    public function previewQuery(string $sampleNumber = '8801711111111', ?string $sampleMessage = null): array
    {
        $integration = $this->integration();
        $apiKey = (string) $integration->credential('api_key', '');
        $maskedKey = filled($apiKey)
            ? ($integration->maskedCredential('api_key') ?: '••••')
            : '(api_key)';

        $defaultMessage = $this->renderTemplate(
            $this->getTemplate(self::TEMPLATE_CUSTOMER_ORDER),
            [
                'order_id' => 'AS-261021-DEMO',
                'customer_name' => 'Demo Customer',
                'phone' => '8801711111111',
                'total' => '1250.00',
                'company_name' => (string) (StoreSettings::get('store_name') ?: config('app.name', 'Store')),
            ],
        );

        return [
            'api_key' => $maskedKey,
            'type' => 'text',
            'number' => $sampleNumber,
            'senderid' => (string) ($integration->credential('sender_id') ?: '(senderid)'),
            'message' => $sampleMessage ?? $defaultMessage,
        ];
    }

    /**
     * @return array{ok: bool, message: string, response_code: int|null, balance: string|null, raw: mixed}
     */
    private function interpretResponse(mixed $payload, string $context): array
    {
        $responseCode = null;
        $balance = null;
        $message = $context.' request failed.';

        if (is_array($payload)) {
            $responseCode = isset($payload['response_code'])
                ? (int) $payload['response_code']
                : (isset($payload['Response_Code']) ? (int) $payload['Response_Code'] : null);

            if (isset($payload['balance'])) {
                $balance = (string) $payload['balance'];
            } elseif (isset($payload['Balance'])) {
                $balance = (string) $payload['Balance'];
            }

            $message = (string) (
                $payload['success_message']
                ?? $payload['error_message']
                ?? $payload['message']
                ?? $payload['Message']
                ?? $message
            );
        } elseif (is_string($payload) && is_numeric(trim($payload))) {
            $responseCode = (int) trim($payload);
            $message = $responseCode === self::SUCCESS_RESPONSE_CODE
                ? $context.' submitted successfully.'
                : $context.' failed with code '.$responseCode.'.';
        } elseif (is_string($payload) && $payload !== '') {
            $message = $payload;
        }

        $ok = $responseCode === self::SUCCESS_RESPONSE_CODE;

        if ($ok && blank($message)) {
            $message = $context.' submitted successfully.';
        }

        if (! $ok && $responseCode !== null) {
            $message = trim($message) !== '' && $message !== $context.' request failed.'
                ? $message
                : $context.' failed with code '.$responseCode.'.';
        }

        return [
            'ok' => $ok,
            'message' => $message,
            'response_code' => $responseCode,
            'balance' => $balance,
            'raw' => $payload,
        ];
    }
}
