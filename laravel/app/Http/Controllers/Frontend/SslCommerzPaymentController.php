<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SslCommerzService;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class SslCommerzPaymentController extends Controller
{
    public function __construct(private SslCommerzService $sslCommerz) {}

    public function success(Request $request): RedirectResponse
    {
        $order = $this->sslCommerz->findOrderFromCallback(
            $request->input('tran_id'),
            $request->input('value_a'),
        );

        if (! $order) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Payment received but the order could not be found.');
        }

        $status = strtoupper((string) $request->input('status', ''));
        $valId = (string) $request->input('val_id', '');
        $validated = false;
        $wasNewlyPaid = false;

        if (in_array($status, ['VALID', 'VALIDATED'], true) && $valId !== '') {
            $validation = $this->sslCommerz->validateTransaction($valId);

            if ($validation['ok'] && is_array($validation['payload'])) {
                $validated = true;
                $wasNewlyPaid = $this->sslCommerz->markOrderPaidIfValid($order, $validation['payload']);
            }
        }

        $order->refresh();

        if ($validated && ($order->payment_status ?? 'pending') === 'paid') {
            if ($wasNewlyPaid) {
                app(TrackingService::class)->trackPurchase($order, $request, flashToSession: false);
            }

            return $this->redirectToOrder($order, 'success', 'Payment successful. Thank you for your order.', signed: true);
        }

        return $this->redirectToOrder(
            $order,
            'error',
            'Payment could not be confirmed yet. If money was deducted, it will be verified shortly.',
        );
    }

    public function fail(Request $request): RedirectResponse
    {
        $order = $this->sslCommerz->findOrderFromCallback(
            $request->input('tran_id'),
            $request->input('value_a'),
        );

        if ($order) {
            return $this->redirectToOrder($order, 'error', 'Payment failed. Your order is saved as unpaid.');
        }

        return redirect()
            ->route('cart.index')
            ->with('error', 'Payment failed.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $order = $this->sslCommerz->findOrderFromCallback(
            $request->input('tran_id'),
            $request->input('value_a'),
        );

        if ($order) {
            return $this->redirectToOrder($order, 'error', 'Payment was cancelled. Your order is saved as unpaid.');
        }

        return redirect()
            ->route('cart.index')
            ->with('error', 'Payment was cancelled.');
    }

    public function ipn(Request $request): Response
    {
        $status = strtoupper((string) $request->input('status', ''));
        $valId = (string) $request->input('val_id', '');

        $order = $this->sslCommerz->findOrderFromCallback(
            $request->input('tran_id'),
            $request->input('value_a'),
        );

        if (! $order) {
            Log::warning('SSLCommerz IPN for unknown order.', [
                'tran_id' => $request->input('tran_id'),
                'status' => $status,
            ]);

            return response('Order not found', 404);
        }

        if (! in_array($status, ['VALID', 'VALIDATED'], true) || $valId === '') {
            return response('IPN ignored', 200);
        }

        $validation = $this->sslCommerz->validateTransaction($valId);

        if (! $validation['ok'] || ! is_array($validation['payload'])) {
            Log::warning('SSLCommerz IPN validation failed.', $this->sslCommerz->sanitizeLogContext([
                'order_id' => $order->id,
                'val_id' => $valId,
                'message' => $validation['message'],
            ]));

            return response('Validation failed', 422);
        }

        $this->sslCommerz->markOrderPaidIfValid($order, $validation['payload']);

        return response('IPN processed', 200);
    }

    private function redirectToOrder(Order $order, string $flashKey, string $message, bool $signed = false): RedirectResponse
    {
        $ownsOrder = (Auth::check() && (int) $order->user_id === (int) Auth::id())
            || in_array($order->id, session()->get('guest_order_ids', []), true);

        if (! $ownsOrder && ! $signed) {
            return redirect()
                ->route('order-track.index')
                ->with($flashKey, $message);
        }

        $url = $signed
            ? URL::temporarySignedRoute('checkout.success', now()->addHours(6), $order)
            : route('checkout.success', $order);

        return redirect()->to($url)->with($flashKey, $message);
    }
}
