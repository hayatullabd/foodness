<?php

namespace App\Support;

use App\Models\IncompleteOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class IncompleteOrderTracker
{
    public const SESSION_TRACKING_KEY = 'incomplete_order_tracking_key';

    /**
     * Upsert the abandoned checkout cart for the current browser session.
     * Captured ONLY when the phone number is a full complete 11-digit number.
     *
     * @param  array{customer_name?: ?string, customer_phone?: ?string, customer_address?: ?string}  $customer
     */
    public function capture(Cart $cart, array $customer = []): ?IncompleteOrder
    {
        if ($cart->isEmpty()) {
            return null;
        }

        $sessionKey = $this->trackingKey();
        $existing = IncompleteOrder::query()->where('session_id', $sessionKey)->first();

        $rawPhone = $this->resolveField($customer, 'customer_phone', $existing?->customer_phone);
        $phone = self::normalizePhone($rawPhone);

        // Incomplete orders must NOT be recorded unless the phone number is a full complete 11-digit number
        if ($phone === null) {
            if ($existing) {
                $existing->delete();
            }

            return null;
        }

        $items = $this->snapshotItems($cart);
        $total = $cart->subtotal();

        if ($items === []) {
            return null;
        }

        $attributes = [
            'session_id' => $sessionKey,
            'user_id' => Auth::guard('web')->user()?->id,
            'cart_items' => $items,
            'total' => $total,
            'customer_name' => $this->resolveField($customer, 'customer_name', $existing?->customer_name),
            'customer_phone' => $phone,
            'customer_address' => $this->resolveField($customer, 'customer_address', $existing?->customer_address),
            ...app(TrafficAttribution::class)->attributesForPersistence(),
        ];

        if ($existing) {
            $existing->fill($attributes)->save();

            return $existing->fresh();
        }

        return IncompleteOrder::query()->create($attributes);
    }

    /**
     * Normalize and validate that the phone number is a complete 11-digit Bangladeshi mobile number.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '8801') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '01')) {
            return $digits;
        }

        return null;
    }

    public static function isValidPhone(?string $phone): bool
    {
        return self::normalizePhone($phone) !== null;
    }

    public function clearForSession(): void
    {
        $sessionKey = session()->get(self::SESSION_TRACKING_KEY);

        if (is_string($sessionKey) && $sessionKey !== '') {
            IncompleteOrder::query()->where('session_id', $sessionKey)->delete();
        }

        session()->forget(self::SESSION_TRACKING_KEY);
    }

    private function trackingKey(): string
    {
        $key = session()->get(self::SESSION_TRACKING_KEY);

        if (! is_string($key) || $key === '') {
            $key = (string) Str::uuid();
            session()->put(self::SESSION_TRACKING_KEY, $key);
        }

        return $key;
    }

    private function snapshotItems(Cart $cart): array
    {
        return $cart->lines()
            ->map(function (array $line): array {
                /** @var Product $product */
                $product = $line['product'];
                /** @var ProductVariant|null $variant */
                $variant = $line['variant'] ?? null;
                $imagePath = $variant?->primaryImagePath() ?: $product->primaryImagePath();

                $variantLabel = $variant ? $variant->label() : null;
                $displayName = $product->name.($variantLabel ? ' ('.$variantLabel.')' : '');

                return [
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'name' => $displayName,
                    'color' => $line['color'] ?? null,
                    'size' => $line['size'] ?? null,
                    'qty' => (int) $line['qty'],
                    'price' => (string) ($line['price'] ?? $product->price),
                    'image' => $imagePath ? asset('storage/'.$imagePath) : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array{customer_name?: ?string, customer_phone?: ?string, customer_address?: ?string}  $customer
     */
    private function resolveField(array $customer, string $key, ?string $fallback): ?string
    {
        if (! array_key_exists($key, $customer)) {
            return $fallback;
        }

        $value = $customer[$key];

        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
