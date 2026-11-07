<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\PaymentMethodService;
use App\Services\TrackingService;
use App\Support\Cart;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function cancelBuyNow(Cart $cart): JsonResponse
    {
        $cart->abandonBuyNow();

        return response()->json([
            'ok' => true,
            'cart_count' => $cart->count(),
            'subtotal' => $cart->subtotal(),
        ]);
    }

    public function index(Cart $cart): View
    {
        return view('store.cart', [
            'lines' => $cart->lines(),
            'subtotal' => $cart->subtotal(),
        ]);
    }

    public function store(Request $request, Cart $cart, TrackingService $tracking): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::query()
            ->active()
            ->with(['variants.color', 'variants.size'])
            ->findOrFail($validated['product_id']);

        $variant = null;
        if (! empty($validated['variant_id'])) {
            $variant = $product->variants->firstWhere('id', (int) $validated['variant_id']);
            if (! $variant) {
                return back()->with('error', 'The selected option is invalid.');
            }
        } elseif ($product->variants->isNotEmpty()) {
            // Default to first variant if none selected explicitly
            $variant = $product->variants->firstWhere('stock', '>', 0) ?? $product->variants->first();
        }

        $isUnlimited = $variant ? (bool) $variant->is_unlimited_stock : (bool) $product->is_unlimited_stock;
        $availableStock = $isUnlimited ? 99999 : ($variant ? (int) $variant->stock : (int) $product->stock);

        if ($availableStock < 1) {
            $label = $variant ? $variant->label() : null;

            return back()->with('error', $product->name.($label ? ' ('.$label.')' : '').' is out of stock.');
        }

        $qty = $validated['qty'] ?? 1;
        $variantLabel = $variant ? $variant->label() : null;
        $successName = $product->name.($variantLabel ? ' ('.$variantLabel.')' : '');

        if ($request->boolean('buy_now')) {
            $cart->startBuyNow($product, $qty, $variant);

            return redirect()
                ->route('checkout.create')
                ->with('success', $successName.' — অর্ডার সম্পন্ন করতে চেকআউট করুন।');
        }

        $cart->add($product, $qty, $variant);

        // Dispatches non-blocking server-side events and flashes dataLayer payload + event_id
        $tracking->trackAddToCart($product, $variant, $qty, $request);

        return redirect()
            ->route('cart.index')
            ->with('success', $successName.' added to your bag.');
    }

    public function storeModal(Request $request, Cart $cart, TrackingService $tracking): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::query()
            ->active()
            ->with(['variants.color', 'variants.size'])
            ->findOrFail($validated['product_id']);

        $variant = null;
        if (! empty($validated['variant_id'])) {
            $variant = $product->variants->firstWhere('id', (int) $validated['variant_id']);
            if (! $variant) {
                return response()->json([
                    'ok' => false,
                    'message' => 'The selected option is invalid.',
                ], 422);
            }
        } elseif ($product->variants->isNotEmpty()) {
            $variant = $product->variants->first(fn ($v) => $v->is_unlimited_stock || $v->stock > 0) ?? $product->variants->first();
        }

        $isUnlimited = $variant ? (bool) $variant->is_unlimited_stock : (bool) $product->is_unlimited_stock;
        $availableStock = $isUnlimited ? 99999 : ($variant ? (int) $variant->stock : (int) $product->stock);

        if ($availableStock < 1) {
            $label = $variant ? $variant->label() : null;

            return response()->json([
                'ok' => false,
                'message' => $product->name.($label ? ' ('.$label.')' : '').' is out of stock.',
            ], 422);
        }

        $qty = $validated['qty'] ?? 1;
        $variantLabel = $variant ? $variant->label() : null;
        $successName = $product->name.($variantLabel ? ' ('.$variantLabel.')' : '');

        if ($request->boolean('buy_now')) {
            $cart->startBuyNow($product, $qty, $variant);

            return response()->json([
                'ok' => true,
                'message' => $successName.' ready to order.',
                'cart_count' => $cart->realCount(),
                'subtotal' => $cart->realSubtotal(),
                'is_buy_now' => true,
                'tracked_event' => null,
                'checkout_data' => app(CheckoutController::class)->modalDataPayload($cart, app(PaymentMethodService::class), $tracking),
            ]);
        }

        $cart->add($product, $qty, $variant);

        $tracked = $tracking->trackAddToCart($product, $variant, $qty, $request, flashToSession: true);

        return response()->json([
            'ok' => true,
            'message' => $successName.' added to your bag.',
            'cart_count' => $cart->count(),
            'subtotal' => $cart->subtotal(),
            'is_buy_now' => false,
            'tracked_event' => $tracked,
            'checkout_data' => null,
        ]);
    }

    public function update(Request $request, Product $product, Cart $cart): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:0', 'max:99'],
            'variant_id' => ['nullable', 'integer'],
        ]);

        $variantId = ! empty($validated['variant_id']) ? (int) $validated['variant_id'] : null;
        $cart->update($product, $validated['qty'], $variantId);

        return redirect()
            ->route('cart.index')
            ->with('success', 'Bag updated.');
    }

    public function destroy(Request $request, Product $product, Cart $cart): RedirectResponse
    {
        $variantId = $request->filled('variant_id') ? $request->integer('variant_id') : null;
        $cart->remove($product, $variantId);

        return redirect()
            ->route('cart.index')
            ->with('success', 'Item removed from your bag.');
    }

    public function destroyModal(Request $request, Cart $cart): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        $variantId = ! empty($validated['variant_id']) ? (int) $validated['variant_id'] : null;

        $cart->adoptBuyNowIfActive();
        $cart->remove($product, $variantId);

        return response()->json([
            'ok' => true,
            'message' => 'Item removed from your bag.',
            'cart_count' => $cart->isBuyNow() ? $cart->realCount() : $cart->count(),
            'is_empty' => $cart->isEmpty(),
            'subtotal' => (float) ($cart->isBuyNow() ? $cart->realSubtotal() : $cart->subtotal()),
            'subtotal_formatted' => Money::format($cart->isBuyNow() ? $cart->realSubtotal() : $cart->subtotal()),
        ]);
    }

    public function updateModal(Request $request, Cart $cart): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer'],
            'qty' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        $variantId = ! empty($validated['variant_id']) ? (int) $validated['variant_id'] : null;

        $cart->adoptBuyNowIfActive();
        $cart->update($product, $validated['qty'], $variantId);

        $count = $cart->isBuyNow() ? $cart->realCount() : $cart->count();
        $subtotal = $cart->isBuyNow() ? $cart->realSubtotal() : $cart->subtotal();

        return response()->json([
            'ok' => true,
            'message' => 'Bag updated.',
            'cart_count' => $count,
            'is_empty' => $cart->isEmpty(),
            'subtotal' => (float) $subtotal,
            'subtotal_formatted' => Money::format($subtotal),
        ]);
    }
}
