<?php

namespace App\Services;

use App\Models\ApiIntegration;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SslCommerzService
{
    public const SANDBOX_SESSION_URL = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';

    public const LIVE_SESSION_URL = 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';

    public const SANDBOX_VALIDATION_URL = 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';

    public const LIVE_VALIDATION_URL = 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';

    public function integration(): ApiIntegration
    {
        return ApiIntegration::forProvider(ApiIntegration::PROVIDER_SSLCOMMERZ);
    }

    public function storeId(): string
    {
        $configVal = config('services.sslcommerz.store_id');
        if (filled($configVal)) {
            return (string) $configVal;
        }

        return (string) $this->integration()->credential('store_id', '');
    }

    public function storePassword(): string
    {
        $configVal = config('services.sslcommerz.store_password');
        if (filled($configVal)) {
            return (string) $configVal;
        }

        return (string) $this->integration()->credential('store_password', '');
    }

    public function isEnabled(): bool
    {
        $storeId = $this->storeId();
        $storePassword = $this->storePassword();

        if (filled(config('services.sslcommerz.store_id')) && filled(config('services.sslcommerz.store_password'))) {
            return true;
        }

        $integration = $this->integration();

        return $integration->is_active && filled($storeId) && filled($storePassword);
    }

    public function isSandbox(): bool
    {
        $configSandbox = config('services.sslcommerz.is_sandbox');
        if ($configSandbox !== null && $configSandbox !== '') {
            return (bool) $configSandbox;
        }

        return (bool) $this->integration()->credential('is_sandbox', true);
    }

    public function sessionUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_SESSION_URL : self::LIVE_SESSION_URL;
    }

    public function validationUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_VALIDATION_URL : self::LIVE_VALIDATION_URL;
    }

    /**
     * Create a hosted checkout session and return the GatewayPageURL.
     *
     * @return array{ok: bool, gateway_url: ?string, sessionkey: ?string, message: string, payload: ?array<string, mixed>}
     */
    public function initiatePayment(Order $order): array
    {
        if (! $this->isEnabled()) {
            return [
                'ok' => false,
                'gateway_url' => null,
                'sessionkey' => null,
                'message' => 'SSLCommerz is not configured or enabled.',
                'payload' => null,
            ];
        }

        $order->loadMissing(['items', 'user']);

        $productNames = $order->items
            ->pluck('product_name')
            ->filter()
            ->take(5)
            ->implode(',');

        if ($productNames === '') {
            $productNames = 'Order '.$order->invoice_no;
        }

        $payload = [
            'store_id' => $this->storeId(),
            'store_passwd' => $this->storePassword(),
            'total_amount' => number_format((float) $order->total, 2, '.', ''),
            'currency' => 'BDT',
            'tran_id' => $order->invoice_no,
            'product_category' => 'fashion',
            'success_url' => route('payments.sslcommerz.success'),
            'fail_url' => route('payments.sslcommerz.fail'),
            'cancel_url' => route('payments.sslcommerz.cancel'),
            'ipn_url' => route('payments.sslcommerz.ipn'),
            'cus_name' => $order->customer_name,
            'cus_email' => $this->customerEmail($order),
            'cus_add1' => $order->customer_address,
            'cus_add2' => '',
            'cus_city' => 'Dhaka',
            'cus_state' => 'Dhaka',
            'cus_postcode' => '1000',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $order->customer_phone,
            'shipping_method' => 'NO',
            'num_of_item' => max(1, (int) $order->items->sum('qty')),
            'product_name' => mb_substr($productNames, 0, 255),
            'product_profile' => 'general',
            'value_a' => (string) $order->id,
            'value_b' => $order->invoice_no,
        ];

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(30)
                ->post($this->sessionUrl(), $payload);
        } catch (ConnectionException $exception) {
            Log::warning('SSLCommerz session connection failed.', $this->sanitizeLogContext([
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]));

            return [
                'ok' => false,
                'gateway_url' => null,
                'sessionkey' => null,
                'message' => 'Could not reach SSLCommerz. Please try again shortly.',
                'payload' => null,
            ];
        } catch (Throwable $exception) {
            Log::error('SSLCommerz session request failed unexpectedly.', $this->sanitizeLogContext([
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]));

            return [
                'ok' => false,
                'gateway_url' => null,
                'sessionkey' => null,
                'message' => 'Online payment could not be started.',
                'payload' => null,
            ];
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];
        $status = strtoupper((string) ($body['status'] ?? ''));
        $gatewayUrl = is_string($body['GatewayPageURL'] ?? null) ? $body['GatewayPageURL'] : null;

        if ($response->successful() && $status === 'SUCCESS' && filled($gatewayUrl)) {
            return [
                'ok' => true,
                'gateway_url' => $gatewayUrl,
                'sessionkey' => is_string($body['sessionkey'] ?? null) ? $body['sessionkey'] : null,
                'message' => 'Session created.',
                'payload' => $body,
            ];
        }

        $reason = is_string($body['failedreason'] ?? null) && $body['failedreason'] !== ''
            ? $body['failedreason']
            : 'SSLCommerz did not return a payment URL.';

        Log::warning('SSLCommerz session initiation failed.', $this->sanitizeLogContext([
            'order_id' => $order->id,
            'http_status' => $response->status(),
            'api_status' => $body['status'] ?? null,
            'reason' => $reason,
        ]));

        return [
            'ok' => false,
            'gateway_url' => null,
            'sessionkey' => null,
            'message' => $reason,
            'payload' => $body !== [] ? $body : null,
        ];
    }

    /**
     * Validate a transaction with the SSLCommerz Order Validation API.
     *
     * @return array{ok: bool, status: ?string, message: string, payload: ?array<string, mixed>}
     */
    public function validateTransaction(string $valId): array
    {
        if (! $this->isEnabled()) {
            return [
                'ok' => false,
                'status' => null,
                'message' => 'SSLCommerz is not configured or enabled.',
                'payload' => null,
            ];
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(30)
                ->get($this->validationUrl(), [
                    'val_id' => $valId,
                    'store_id' => $this->storeId(),
                    'store_passwd' => $this->storePassword(),
                    'format' => 'json',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('SSLCommerz validation connection failed.', $this->sanitizeLogContext([
                'val_id' => $valId,
                'error' => $exception->getMessage(),
            ]));

            return [
                'ok' => false,
                'status' => null,
                'message' => 'Could not reach SSLCommerz validation API.',
                'payload' => null,
            ];
        } catch (Throwable $exception) {
            Log::error('SSLCommerz validation failed unexpectedly.', $this->sanitizeLogContext([
                'val_id' => $valId,
                'error' => $exception->getMessage(),
            ]));

            return [
                'ok' => false,
                'status' => null,
                'message' => 'Payment validation failed unexpectedly.',
                'payload' => null,
            ];
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];
        $status = strtoupper((string) ($body['status'] ?? ''));

        if ($response->successful() && in_array($status, ['VALID', 'VALIDATED'], true)) {
            return [
                'ok' => true,
                'status' => $status,
                'message' => 'Transaction validated.',
                'payload' => $body,
            ];
        }

        return [
            'ok' => false,
            'status' => $status !== '' ? $status : null,
            'message' => 'Transaction validation failed.',
            'payload' => $body !== [] ? $body : null,
        ];
    }

    /**
     * Mark the order paid with strict atomic locking, idempotency check, and amount/currency verification.
     * Returns true ONLY if the order was newly transitioned to paid.
     * Returns false if the order was already paid or validation checks failed.
     *
     * @param  array<string, mixed>  $payload
     */
    public function markOrderPaidIfValid(Order $order, array $payload): bool
    {
        $lock = Cache::lock('sslcommerz_pay_order_'.$order->id, 15);

        return (bool) $lock->block(10, function () use ($order, $payload): bool {
            return DB::transaction(function () use ($order, $payload): bool {
                $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();

                if (! $lockedOrder) {
                    return false;
                }

                // Idempotency: If already marked paid, return false without re-triggering post-payment actions
                if ($lockedOrder->payment_status === 'paid') {
                    return false;
                }

                $amount = isset($payload['amount']) ? (string) $payload['amount'] : null;
                $currencyAmount = isset($payload['currency_amount']) ? (string) $payload['currency_amount'] : null;
                $currencyType = strtoupper((string) ($payload['currency_type'] ?? $payload['currency'] ?? ''));
                $tranId = (string) ($payload['tran_id'] ?? '');

                if ($tranId !== '' && $tranId !== $lockedOrder->invoice_no) {
                    Log::warning('SSLCommerz tran_id mismatch.', $this->sanitizeLogContext([
                        'order_id' => $lockedOrder->id,
                        'expected' => $lockedOrder->invoice_no,
                        'received' => $tranId,
                    ]));

                    return false;
                }

                $expected = number_format((float) $lockedOrder->total, 2, '.', '');
                $candidate = $currencyAmount ?: $amount;

                if ($candidate === null || bccomp($expected, number_format((float) $candidate, 2, '.', ''), 2) !== 0) {
                    Log::warning('SSLCommerz amount mismatch.', $this->sanitizeLogContext([
                        'order_id' => $lockedOrder->id,
                        'expected' => $expected,
                        'received' => $candidate,
                    ]));

                    return false;
                }

                if ($currencyType !== '' && $currencyType !== 'BDT') {
                    Log::warning('SSLCommerz currency mismatch.', $this->sanitizeLogContext([
                        'order_id' => $lockedOrder->id,
                        'received' => $currencyType,
                    ]));

                    return false;
                }

                $lockedOrder->forceFill([
                    'payment_method' => 'sslcommerz',
                    'payment_status' => 'paid',
                ])->save();

                return true;
            });
        });
    }

    public function findOrderFromCallback(?string $tranId, ?string $valueA = null): ?Order
    {
        if (filled($valueA) && ctype_digit((string) $valueA)) {
            $order = Order::query()->find((int) $valueA);

            if ($order) {
                return $order;
            }
        }

        if (filled($tranId)) {
            return Order::query()->where('invoice_no', $tranId)->first();
        }

        return null;
    }

    /**
     * Sanitize log context to scrub credentials, passwords, card data, and tokens.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function sanitizeLogContext(array $context): array
    {
        $sensitiveKeys = [
            'store_passwd', 'store_password', 'password', 'passwd',
            'card_no', 'card_number', 'card_cvv', 'cvv', 'token',
            'secret', 'api_key', 'pin',
        ];

        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = $this->sanitizeLogContext($value);
            } elseif (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
                $context[$key] = '[REDACTED]';
            }
        }

        return $context;
    }

    private function customerEmail(Order $order): string
    {
        $email = $order->user?->email;

        if (filled($email)) {
            return (string) $email;
        }

        return 'customer'.$order->id.'@orders.foodness.local';
    }
}
