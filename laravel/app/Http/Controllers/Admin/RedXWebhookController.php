<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierApi;
use App\Services\Courier\CourierWebhookOrderUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RedXWebhookController extends Controller
{
    public function __construct(
        private readonly CourierWebhookOrderUpdater $updater,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            Log::info('RedX webhook received', [
                'ip' => $request->ip(),
            ]);

            if (! $this->authorizeWebhook($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $tracking = $request->input('tracking_number')
                ?? $request->input('tracking_id')
                ?? $request->input('parcel_id');
            $invoice = $request->input('invoice_number') ?? $request->input('invoice_id');
            $status = $request->input('status');

            if (blank($tracking) || blank($status)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required fields: tracking_number or status',
                ], 400);
            }

            $order = $this->updater->findOrder($tracking, $invoice);

            if ($order === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $this->updater->applyRemoteStatus(
                $order,
                is_string($status) ? $status : null,
                CourierApi::PROVIDER_REDX,
                is_string($tracking) ? $tracking : null,
            );

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ]);
        } catch (Throwable $e) {
            Log::warning('RedX webhook soft-failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    private function authorizeWebhook(Request $request): bool
    {
        $provided = CourierApi::normalizeBearerToken(
            $request->bearerToken()
            ?: $request->header('X-Redx-Signature')
            ?: $request->header('X-Redx-Token')
        );

        return CourierApi::webhookTokenMatches(CourierApi::PROVIDER_REDX, $provided);
    }
}
