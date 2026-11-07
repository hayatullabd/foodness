<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierApi;
use App\Services\Courier\CourierWebhookOrderUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SteadfastWebhookController extends Controller
{
    public function __construct(
        private readonly CourierWebhookOrderUpdater $updater,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            Log::info('Steadfast webhook received', [
                'ip' => $request->ip(),
            ]);

            if (! $this->authorizeWebhook($request)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }

            $tracking = $request->input('consignment_id')
                ?? $request->input('tracking_code')
                ?? $request->input('tracking_number');
            $invoice = $request->input('invoice') ?? $request->input('invoice_id');
            $status = $request->input('status')
                ?? $request->input('delivery_status')
                ?? $request->input('tracking_message');

            if (blank($tracking) && blank($invoice)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'consignment_id or invoice is required',
                ], 400);
            }

            $order = $this->updater->findOrder($tracking, $invoice);

            if ($order === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found',
                ], 404);
            }

            $this->updater->applyRemoteStatus(
                $order,
                is_string($status) ? $status : null,
                CourierApi::PROVIDER_STEADFAST,
                is_string($tracking) ? $tracking : null,
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook received successfully.',
            ]);
        } catch (Throwable $e) {
            Log::warning('Steadfast webhook soft-failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
            ], 500);
        }
    }

    private function authorizeWebhook(Request $request): bool
    {
        if (CourierApi::webhookTokenMatches(CourierApi::PROVIDER_STEADFAST, $request->bearerToken())) {
            return true;
        }

        $secretKey = $request->header('Secret-Key') ?? $request->header('Secretkey') ?? $request->query('token');
        $apiKey = $request->header('Api-Key') ?? $request->header('Apikey');

        if (filled($secretKey) || filled($apiKey)) {
            return CourierApi::query()
                ->where('provider', CourierApi::PROVIDER_STEADFAST)
                ->where('is_active', true)
                ->where(function ($q) use ($secretKey, $apiKey): void {
                    if (filled($secretKey)) {
                        $q->where('secret_key', $secretKey)->orWhere('token', $secretKey);
                    }
                    if (filled($apiKey)) {
                        $q->orWhere('api_key', $apiKey);
                    }
                })
                ->exists();
        }

        return false;
    }
}
