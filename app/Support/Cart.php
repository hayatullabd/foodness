<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingCharge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Cart
{
    public const SESSION_KEY = 'cart';

    public const BUY_NOW_KEY = 'buy_now';

    private bool $buyNow = false;

    /**
     * @return array<string, array{product_id: int, variant_id?: ?int, qty: int}>
     */
    public function items(): array
    {
        return Session::get($this->sessionKey(), []);
    }

    public function isBuyNow(): bool
    {
        return $this->buyNow;
    }

    public function adoptBuyNowIfActive(): void
    {
        $this->buyNow = (bool) Session::get('checkout_is_buy_now', false);
    }

    public function startBuyNow(Product $product, int $qty = 1, ?ProductVariant $variant = null): void
    {
        $variantId = $variant?->id;
        $isUnlimited = $variant ? (bool) $variant->is_unlimited_stock : (bool) $product->is_unlimited_stock;
        $availableStock = $isUnlimited ? 99999 : ($variant ? (int) $variant->stock : (int) $product->stock);
        $qty = min(max(1, $qty), max(1, $availableStock));

        Session::put(self::BUY_NOW_KEY, [
            self::itemKey($product->id, $variantId) => [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'qty' => $qty,
            ],
        ]);
        Session::put('checkout_is_buy_now', true);
        $this->buyNow = true;
    }

    public function abandonBuyNow(): void
    {
        Session::forget(self::BUY_NOW_KEY);
        Session::forget('checkout_is_buy_now');
        $this->buyNow = false;
    }

    public function realCount(): int
    {
        return (int) collect(Session::get(self::SESSION_KEY, []))->sum('qty');
    }

    public function realSubtotal(): string
    {
        $wasBuyNow = $this->buyNow;
        $this->buyNow = false;
        $subtotal = $this->subtotal();
        $this->buyNow = $wasBuyNow;

        return $subtotal;
    }

    private function sessionKey(): string
    {
        return $this->buyNow ? self::BUY_NOW_KEY : self::SESSION_KEY;
    }

    public static function itemKey(int $productId, ?int $variantId = null): string
    {
        return $variantId ? "{$productId}:{$variantId}" : (string) $productId;
    }

    public function count(): int
    {
        return (int) collect($this->items())->sum('qty');
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function add(Product $product, int $qty = 1, ?ProductVariant $variant = null, ?string $customPrice = null): void
    {
        $items = $this->items();
        $variantId = $variant?->id;
        $key = self::itemKey($product->id, $variantId);

        $currentQty = $items[$key]['qty'] ?? 0;
        $isUnlimited = $variant ? (bool) $variant->is_unlimited_stock : (bool) $product->is_unlimited_stock;
        $availableStock = $isUnlimited ? 99999 : ($variant ? (int) $variant->stock : (int) $product->stock);
        $newQty = min($currentQty + $qty, max(1, $availableStock));

        $itemData = [
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'qty' => $newQty,
        ];

        if ($customPrice !== null) {
            $itemData['custom_price'] = $customPrice;
        }

        $items[$key] = $itemData;

        Session::put($this->sessionKey(), $items);
    }

    public function update(Product $product, int $qty, ?int $variantId = null): void
    {
        $items = $this->items();
        $key = self::itemKey($product->id, $variantId);

        if (! isset($items[$key]) && $variantId === null) {
            $matching = array_keys(array_filter($items, fn ($i) => (int) $i['product_id'] === $product->id));
            if (count($matching) === 1) {
                $key = (string) $matching[0];
            }
        }

        if ($qty <= 0) {
            unset($items[$key]);
            Session::put($this->sessionKey(), $items);

            return;
        }

        $entry = $items[$key] ?? ['product_id' => $product->id, 'variant_id' => $variantId];
        $actualVariantId = $entry['variant_id'] ?? $variantId;
        $variant = $actualVariantId ? ProductVariant::query()->find($actualVariantId) : null;
        $isUnlimited = $variant ? (bool) $variant->is_unlimited_stock : (bool) $product->is_unlimited_stock;
        $availableStock = $isUnlimited ? 99999 : ($variant ? (int) $variant->stock : (int) $product->stock);

        $items[$key] = [
            'product_id' => $product->id,
            'variant_id' => $actualVariantId,
            'qty' => min($qty, max(1, $availableStock)),
        ];

        Session::put($this->sessionKey(), $items);
    }

    public function remove(Product $product, ?int $variantId = null): void
    {
        $items = $this->items();
        $key = self::itemKey($product->id, $variantId);

        if (isset($items[$key])) {
            unset($items[$key]);
        } else {
            foreach ($items as $k => $item) {
                if ((int) ($item['product_id'] ?? 0) === $product->id) {
                    if ($variantId === null || (int) ($item['variant_id'] ?? 0) === (int) $variantId) {
                        unset($items[$k]);
                    }
                }
            }
        }

        Session::put($this->sessionKey(), $items);
    }

    public function clear(): void
    {
        Session::forget($this->sessionKey());

        if ($this->buyNow) {
            Session::forget('checkout_is_buy_now');
            $this->buyNow = false;

            return;
        }

        Session::forget('cart_override_delivery_charge');
    }

    /**
     * @return Collection<int, array{item_key: string, product: Product, variant: ?ProductVariant, variant_id: ?int, color: ?string, size: ?string, unit_price: string, price: string, qty: int, line_total: string}>
     */
    public function lines(): Collection
    {
        $items = $this->items();

        if ($items === []) {
            return collect();
        }

        $productIds = collect($items)->pluck('product_id')->filter()->unique();
        $products = Product::query()
            ->active()
            ->with(['images', 'category'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $variantIds = collect($items)->pluck('variant_id')->filter()->unique();
        $variants = $variantIds->isNotEmpty()
            ? ProductVariant::query()
                ->with(['color', 'size', 'product.images'])
                ->whereIn('id', $variantIds)
                ->get()
                ->keyBy('id')
            : collect();

        return collect($items)
            ->map(function (array $item, string|int $key) use ($products, $variants): ?array {
                /** @var Product|null $product */
                $product = $products->get($item['product_id']);

                if (! $product) {
                    return null;
                }

                /** @var ProductVariant|null $variant */
                $variant = ! empty($item['variant_id']) ? $variants->get($item['variant_id']) : null;
                if ($variant && ! $variant->relationLoaded('product')) {
                    $variant->setRelation('product', $product);
                }
                $qty = (int) $item['qty'];

                $unitPrice = ! empty($item['custom_price'])
                    ? (string) $item['custom_price']
                    : ($variant ? $variant->effectivePrice() : (string) $product->price);
                $lineTotal = bcmul($unitPrice, (string) $qty, 2);

                $attrMap = $variant ? $variant->attributeMap() : [];
                $color = $variant?->color?->name ?: ($attrMap['Color'] ?? null);
                $size = ($variant?->size?->name ?? $variant?->size_name) ?: ($attrMap['Size'] ?? null);

                return [
                    'item_key' => (string) $key,
                    'product' => $product,
                    'variant' => $variant,
                    'variant_id' => $variant?->id,
                    'variation_name' => $variant ? $variant->label() : null,
                    'attributes' => $attrMap,
                    'color' => $color,
                    'size' => $size,
                    'unit_price' => $unitPrice,
                    'price' => $unitPrice,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];
            })
            ->filter()
            ->values();
    }

    public function subtotal(): string
    {
        return (string) $this->lines()->reduce(
            fn (string $carry, array $line): string => bcadd($carry, $line['line_total'], 2),
            '0.00',
        );
    }

    public function setOverrideDeliveryCharge(?string $amount): void
    {
        if ($amount === null) {
            Session::forget('cart_override_delivery_charge');
        } else {
            Session::put('cart_override_delivery_charge', $amount);
        }
    }

    public function qualifiesForFreeShippingThreshold(): bool
    {
        $threshold = StoreSettings::get('free_shipping_threshold');
        if (! filled($threshold) || ! is_numeric($threshold) || (float) $threshold <= 0) {
            return false;
        }

        return (float) $this->subtotal() >= (float) $threshold;
    }

    public function customDeliveryCharge(): ?string
    {
        if (Session::has('cart_override_delivery_charge')) {
            return (string) Session::get('cart_override_delivery_charge');
        }

        if ($this->qualifiesForFreeShippingThreshold()) {
            return '0.00';
        }

        $lines = $this->lines();

        if ($lines->isEmpty()) {
            return null;
        }

        if ($lines->every(function (array $line): bool {
            /** @var ProductVariant|null $variant */
            $variant = $line['variant'] ?? null;
            if ($variant) {
                return $variant->isFreeDelivery();
            }

            return (bool) $line['product']->free_delivery;
        })) {
            return '0.00';
        }

        $customCharges = $lines
            ->map(function (array $line): ?string {
                /** @var ProductVariant|null $variant */
                $variant = $line['variant'] ?? null;
                if ($variant && $variant->delivery_charge_type === 'custom') {
                    return $variant->custom_delivery_inside !== null ? (string) $variant->custom_delivery_inside : null;
                }

                return $line['product']->delivery_charge !== null ? (string) $line['product']->delivery_charge : null;
            })
            ->filter(fn (?string $val): bool => $val !== null && $val !== '');

        if ($customCharges->isEmpty()) {
            return null;
        }

        $maxCharge = $customCharges->max();

        return number_format((float) $maxCharge, 2, '.', '');
    }

    /**
     * Get effective delivery charge for a specific shipping area across all cart items.
     */
    public function deliveryChargeForArea(int $shippingChargeId): ?string
    {
        if (Session::has('cart_override_delivery_charge')) {
            return (string) Session::get('cart_override_delivery_charge');
        }

        if ($this->qualifiesForFreeShippingThreshold()) {
            return '0.00';
        }

        $lines = $this->lines();

        if ($lines->isEmpty()) {
            return null;
        }

        if ($lines->every(function (array $line): bool {
            /** @var ProductVariant|null $variant */
            $variant = $line['variant'] ?? null;
            if ($variant) {
                return $variant->isFreeDelivery();
            }

            return (bool) $line['product']->free_delivery;
        })) {
            return '0.00';
        }

        $shippingCharge = ShippingCharge::query()->find($shippingChargeId);
        $standardAmount = $shippingCharge !== null ? (float) $shippingCharge->amount : null;

        $hasOverride = false;
        $charges = [];

        foreach ($lines as $line) {
            /** @var ProductVariant|null $variant */
            $variant = $line['variant'] ?? null;
            $lineCharge = $variant
                ? $variant->deliveryChargeForArea($shippingChargeId)
                : $line['product']->deliveryChargeForArea($shippingChargeId);

            if ($lineCharge !== null) {
                $hasOverride = true;
                $charges[] = (float) $lineCharge;
            } elseif ($standardAmount !== null) {
                $charges[] = $standardAmount;
            }
        }

        if (! $hasOverride || empty($charges)) {
            return null;
        }

        return number_format(max($charges), 2, '.', '');
    }
}
