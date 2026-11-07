<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = OrderStatus::query()->ordersSection()->where('slug', 'pending')->first()
            ?? OrderStatus::query()->ordersSection()->ordered()->first()
            ?? OrderStatus::query()->ordered()->first();

        $subtotal = fake()->randomFloat(2, 200, 5000);
        $shipping = fake()->randomFloat(2, 0, 120);

        return [
            'invoice_no' => 'ORD-'.now()->format('ymd').'-'.fake()->unique()->numerify('####'),
            'customer_name' => fake()->name(),
            'customer_phone' => '01'.fake()->numerify('#########'),
            'customer_address' => fake()->address(),
            'subtotal' => $subtotal,
            'shipping_charge' => $shipping,
            'discount' => 0,
            'total' => $subtotal + $shipping,
            'status' => $status?->slug ?? 'pending',
            'order_status_id' => $status?->id,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'delivery_division' => null,
            'delivery_district' => null,
            'delivery_upazila' => null,
            'notes' => null,
            'admin_notes' => null,
            'ip_address' => fake()->ipv4(),
            'fraud_score' => null,
            'fraud_report' => null,
            'tracking_number' => null,
            'tracking_url' => null,
            'courier_provider' => null,
            'source' => 'direct',
            'source_platform' => 'direct',
            'landing_url' => null,
            'referrer' => null,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_content' => null,
            'utm_term' => null,
            'fbclid' => null,
            'gclid' => null,
            'ttclid' => null,
            'user_agent' => null,
            'attribution_json' => null,
        ];
    }

    public function status(string $slug, ?string $section = null): static
    {
        return $this->state(function (array $attributes) use ($slug, $section): array {
            $isCourier = $section === 'courier'
                || (filled($attributes['tracking_number'] ?? null));

            $targetSection = $section ?? ($isCourier ? OrderStatus::SECTION_COURIER : OrderStatus::SECTION_ORDERS);

            $status = OrderStatus::query()
                ->where('section', $targetSection)
                ->where('slug', $slug)
                ->first()
                ?? OrderStatus::query()->where('slug', $slug)->first();

            if (! $status) {
                $normalizedSlug = match ($slug) {
                    'in_courier' => 'processing',
                    'on_the_way' => 'shipped',
                    'completed' => 'delivered',
                    'courier_returned' => 'returned',
                    'unpaid' => 'pending',
                    default => $slug,
                };

                $status = OrderStatus::query()
                    ->where('section', $targetSection)
                    ->where('slug', $normalizedSlug)
                    ->first()
                    ?? OrderStatus::query()->where('slug', $normalizedSlug)->firstOrFail();
            }

            return [
                'status' => $status->slug,
                'order_status_id' => $status->id,
            ];
        });
    }
}
