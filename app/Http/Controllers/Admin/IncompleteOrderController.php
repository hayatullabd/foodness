<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudSetting;
use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Services\BdCourierFraudService;
use App\Services\FraudOrderGate;
use App\Services\OrderRestrictionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IncompleteOrderController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.orders.index', ['tab' => 'incomplete']);
    }

    public function fraudCheck(IncompleteOrder $incompleteOrder, BdCourierFraudService $fraudService): RedirectResponse
    {
        $result = $fraudService->applyToIncompleteOrder($incompleteOrder);

        if (! $result['ok']) {
            return redirect()
                ->route('admin.orders.index', ['tab' => 'incomplete'])
                ->with('error', $result['message']);
        }

        $scoreLabel = $result['score'] !== null
            ? number_format($result['score'], 2).'% ratio'
            : 'no summary score';

        return redirect()
            ->route('admin.orders.index', ['tab' => 'incomplete'])
            ->with('success', 'Courier ratio check complete: '.$scoreLabel.'.');
    }

    public function bulk(Request $request, BdCourierFraudService $fraudService): RedirectResponse
    {
        $validated = $request->validate([
            'incomplete_order_ids' => ['required', 'array', 'min:1'],
            'incomplete_order_ids.*' => ['integer', 'exists:incomplete_orders,id'],
            'action' => ['required', 'string', Rule::in(['fraud_check', 'delete'])],
        ]);

        $incompleteOrders = IncompleteOrder::query()
            ->whereIn('id', $validated['incomplete_order_ids'])
            ->get();

        if ($validated['action'] === 'delete') {
            IncompleteOrder::query()->whereIn('id', $validated['incomplete_order_ids'])->delete();

            return back()->with('success', $incompleteOrders->count().' incomplete order(s) deleted.');
        }

        if (! FraudSetting::current()->isConfigured()) {
            return back()->with('error', 'Courier Ratio API is disabled or missing a key. Configure it under Manage Courier Ratio API.');
        }

        $checked = 0;
        $failed = 0;

        foreach ($incompleteOrders as $incompleteOrder) {
            $result = $fraudService->applyToIncompleteOrder($incompleteOrder, softFail: true);

            if ($result['ok']) {
                $checked++;
            } else {
                $failed++;
            }
        }

        $message = $checked.' incomplete order(s) ratio-checked.';

        if ($failed > 0) {
            $message .= ' '.$failed.' could not be checked.';
        }

        return back()->with($checked > 0 ? 'success' : 'error', $message);
    }

    public function accept(
        IncompleteOrder $incompleteOrder,
        BdCourierFraudService $fraudService,
        OrderRestrictionService $orderRestrictions,
        FraudOrderGate $fraudOrderGate,
    ): RedirectResponse {
        $items = $incompleteOrder->lineItems();

        if ($items === []) {
            return redirect()
                ->route('admin.orders.index', ['tab' => 'incomplete'])
                ->with('error', 'This incomplete order has no products.');
        }

        $restrictionMessage = $orderRestrictions->firstBlockingMessage(
            $items,
            $incompleteOrder->customer_phone,
            $incompleteOrder->user_id,
        );

        if ($restrictionMessage !== null) {
            return redirect()
                ->route('admin.orders.index', ['tab' => 'incomplete'])
                ->with('error', $restrictionMessage);
        }

        $fraudGateMessage = $fraudOrderGate->blockingMessage($incompleteOrder->customer_phone);

        if ($fraudGateMessage !== null) {
            return redirect()
                ->route('admin.orders.index', ['tab' => 'incomplete'])
                ->with('error', $fraudGateMessage);
        }

        $this->autoCheckMissingFraud($incompleteOrder, $fraudService);
        $incompleteOrder->refresh();

        $order = DB::transaction(function () use ($incompleteOrder, $items): Order {
            $subtotal = $this->subtotalFromItems($items);
            $shippingAmount = '0.00';
            $discount = '0.00';
            $total = bcadd(bcadd($subtotal, $shippingAmount, 2), $discount, 2);
            $status = OrderStatus::query()->where('slug', 'incomplete_to_confirmed')->first()
                ?? OrderStatus::query()->where('slug', 'confirmed')->first()
                ?? OrderStatus::query()->where('slug', 'pending')->first();

            $order = Order::query()->create([
                'invoice_no' => $this->generateInvoiceNumber(),
                'user_id' => $incompleteOrder->user_id,
                'customer_name' => $incompleteOrder->customer_name ?: 'Guest',
                'customer_phone' => $incompleteOrder->customer_phone ?: '00000000000',
                'customer_address' => $incompleteOrder->customer_address ?: 'Address not provided',
                'subtotal' => $subtotal,
                'shipping_charge' => $shippingAmount,
                'discount' => $discount,
                'total' => $total,
                'status' => $status?->slug ?? 'incomplete_to_confirmed',
                'order_status_id' => $status?->id,
                'payment_method' => 'cod',
                'payment_status' => 'pending',
                'notes' => null,
                'ip_address' => null,
                'fraud_score' => $incompleteOrder->fraud_score,
                'fraud_report' => $incompleteOrder->fraud_report,
                'source' => 'incomplete',
                'source_platform' => $incompleteOrder->source_platform,
                'landing_url' => $incompleteOrder->landing_url,
                'referrer' => $incompleteOrder->referrer,
                'utm_source' => $incompleteOrder->utm_source,
                'utm_medium' => $incompleteOrder->utm_medium,
                'utm_campaign' => $incompleteOrder->utm_campaign,
                'utm_content' => $incompleteOrder->utm_content,
                'utm_term' => $incompleteOrder->utm_term,
                'fbclid' => $incompleteOrder->fbclid,
                'gclid' => $incompleteOrder->gclid,
                'ttclid' => $incompleteOrder->ttclid,
                'user_agent' => $incompleteOrder->user_agent,
                'attribution_json' => $incompleteOrder->attribution_json,
            ]);

            foreach ($items as $item) {
                $productId = isset($item['product_id']) ? (int) $item['product_id'] : null;
                $product = $productId ? Product::query()->find($productId) : null;
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $price = (string) ($item['price'] ?? $product?->price ?? '0');

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'product_name' => (string) ($item['name'] ?? $product?->name ?? 'Product'),
                    'source' => 'incomplete',
                    'price' => $price,
                    'qty' => $qty,
                ]);

                if ($product) {
                    $product->stock = max(0, (int) $product->stock - $qty);
                    $product->save();
                }
            }

            $incompleteOrder->delete();

            return $order;
        });

        if ($order->fraud_score === null && FraudSetting::current()->isConfigured()) {
            $fraudService->applyToOrder($order, softFail: true);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Incomplete order converted to a regular order.');
    }

    public function destroy(IncompleteOrder $incompleteOrder): RedirectResponse
    {
        $incompleteOrder->delete();

        return redirect()
            ->route('admin.orders.index', ['tab' => 'incomplete'])
            ->with('success', 'Incomplete order deleted.');
    }

    private function autoCheckMissingFraud(IncompleteOrder $incompleteOrder, BdCourierFraudService $fraudService): void
    {
        if ($incompleteOrder->fraud_score !== null || ! FraudSetting::current()->isConfigured()) {
            return;
        }

        if (blank($incompleteOrder->customer_phone)) {
            return;
        }

        $lockKey = 'incomplete-order-fraud-auto:'.$incompleteOrder->id;

        if (! Cache::add($lockKey, true, now()->addMinutes(5))) {
            return;
        }

        $fraudService->applyToIncompleteOrder($incompleteOrder, softFail: true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function subtotalFromItems(array $items): string
    {
        return (string) collect($items)->reduce(
            function (string $carry, array $item): string {
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $price = (string) ($item['price'] ?? '0');

                return bcadd($carry, bcmul($price, (string) $qty, 2), 2);
            },
            '0.00',
        );
    }

    private function generateInvoiceNumber(): string
    {
        return Order::generateInvoiceNumber();
    }
}
