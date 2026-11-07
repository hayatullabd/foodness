<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierApi;
use App\Services\Courier\CourierWebhookOrderUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CarrybeeWebhookController extends Controller
{
    public function __construct(
        private readonly CourierWebhookOrderUpdater $updater,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $secret = $request->header('X-CB-Webhook-Integration-Header');
        $secret = is_string($secret) ? trim($secret) : '';

        try {
            Log::info('Carrybee webhook received', [
                'ip' => $request->ip(),
                'event' => $request->input('event'),
            ]);

            if (! $this->authorizeWebhook($secret)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $event = strtolower(trim((string) $request->input('event', '')));
            $tracking = $request->input('consignment_id');
            $invoice = $request->input('merchant_order_id');

            if ($event === 'order.create-failed') {
                Log::warning('Carrybee order create failed', [
                    'merchant_order_id' => $invoice,
                    'reason' => $request->input('reason'),
                    'causes' => $request->input('causes'),
                ]);

                return $this->accepted($secret);
            }

            if ($event === 'order.paid') {
                return $this->accepted($secret);
            }

            if (filled($tracking) || filled($invoice)) {
                $order = $this->updater->findOrder($tracking, $invoice);

                if ($order !== null) {
                    $status = $event !== '' ? preg_replace('/^order\./', '', $event) : null;

                    $this->updater->applyRemoteStatus(
                        $order,
                        is_string($status) ? $status : null,
                        CourierApi::PROVIDER_CARRYBEE,
                        is_string($tracking) ? $tracking : null,
                    );
                }
            }

            return $this->accepted($secret);
        } catch (Throwable $e) {
            Log::warning('Carrybee webhook soft-failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->accepted($secret);
        }
    }

    private function authorizeWebhook(string $secret): bool
    {
        $expected = CourierApi::query()
            ->where('provider', CourierApi::PROVIDER_CARRYBEE)
            ->whereNotNull('token')
            ->pluck('token')
            ->map(fn ($token) => is_string($token) ? trim($token) : '')
            ->filter()
            ->values();

        if ($expected->isEmpty()) {
            return false;
        }

        if ($secret === '') {
            return false;
        }

        return $expected->contains(fn (string $token) => hash_equals($token, $secret));
    }

    private function accepted(string $secret): JsonResponse
    {
        $response = response()->json([
            'error' => false,
            'message' => 'Accepted',
        ], 202);

        if ($secret !== '') {
            $response->header('X-CB-Webhook-Integration-Header', $secret);
        }

        return $response;
    }
}
