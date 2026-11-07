<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'slug',
    'product_selection_type',
    'show_quantity',
    'product_id',
    'custom_price',
    'custom_html',
    'custom_css',
    'custom_js',
    'checkout_heading',
    'checkout_subheading',
    'product_selection_label',
    'variant_label',
    'checkout_button_text',
    'checkout_fields_config',
    'checkout_color',
    'delivery_charge_type',
    'custom_delivery_inside',
    'custom_delivery_outside',
    'variant_delivery_charges',
    'fb_pixel_id',
    'views_count',
    'orders_count',
    'is_active',
])]
class LandingPage extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_quantity' => 'boolean',
            'checkout_fields_config' => 'array',
            'custom_price' => 'decimal:2',
            'custom_delivery_inside' => 'decimal:2',
            'custom_delivery_outside' => 'decimal:2',
            'variant_delivery_charges' => 'array',
            'views_count' => 'integer',
            'orders_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<LandingPageProduct, $this>
     */
    public function landingPageProducts(): HasMany
    {
        return $this->hasMany(LandingPageProduct::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'landing_page_products')
            ->withPivot(['custom_price', 'is_default', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function isMultipleChoice(): bool
    {
        return ($this->product_selection_type ?? 'single') === 'multiple';
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function defaultProduct(): ?Product
    {
        $landingPageProducts = $this->relationLoaded('landingPageProducts')
            ? $this->landingPageProducts
            : $this->landingPageProducts()->get();

        $defaultPivot = $landingPageProducts->firstWhere('is_default', true)
            ?? $landingPageProducts->first();

        if ($defaultPivot) {
            if ($defaultPivot->relationLoaded('product') && $defaultPivot->product) {
                return $defaultPivot->product;
            }

            if ($this->relationLoaded('products')) {
                $found = $this->products->firstWhere('id', $defaultPivot->product_id);
                if ($found) {
                    return $found;
                }
            }
        }

        return $this->product;
    }

    public function effectivePrice(?ProductVariant $variant = null, ?int $productId = null): string
    {
        if ($variant !== null && (float) $variant->price > 0) {
            return (string) $variant->price;
        }

        $landingPageProducts = $this->relationLoaded('landingPageProducts')
            ? $this->landingPageProducts
            : $this->landingPageProducts()->get();

        if ($productId !== null) {
            $lpp = $landingPageProducts->firstWhere('product_id', $productId);
            if ($lpp && $lpp->custom_price !== null && (float) $lpp->custom_price > 0) {
                return (string) $lpp->custom_price;
            }

            if ($this->relationLoaded('products')) {
                $prod = $this->products->firstWhere('id', $productId);
                if ($prod) {
                    return (string) $prod->price;
                }
            }
        }

        $defaultLpp = $landingPageProducts->firstWhere('is_default', true)
            ?? $landingPageProducts->first();

        if ($defaultLpp && $defaultLpp->custom_price !== null && (float) $defaultLpp->custom_price > 0) {
            return (string) $defaultLpp->custom_price;
        }

        if ($this->custom_price !== null && (float) $this->custom_price > 0) {
            return (string) $this->custom_price;
        }

        $prod = $this->defaultProduct() ?? $this->product;

        return (string) ($prod?->price ?? '0.00');
    }

    /**
     * Get resolved delivery charge config for a specific variant.
     *
     * @return array{type: string, inside: float|null, outside: float|null}
     */
    public function variantDeliveryConfig(?ProductVariant $variant): array
    {
        // 1. If page is explicitly set to Free Delivery campaign, whole page has free delivery
        if ($this->delivery_charge_type === 'free') {
            return [
                'type' => 'free',
                'inside' => 0.0,
                'outside' => 0.0,
            ];
        }

        // 2. If page is explicitly set to Custom Campaign delivery rates
        if ($this->delivery_charge_type === 'custom') {
            return [
                'type' => 'custom',
                'inside' => $this->custom_delivery_inside !== null ? (float) $this->custom_delivery_inside : null,
                'outside' => $this->custom_delivery_outside !== null ? (float) $this->custom_delivery_outside : null,
            ];
        }

        // 3. Default: Automatically inherit from Variant and Product settings
        if ($variant) {
            $vType = $variant->delivery_charge_type ?? 'default';
            if ($vType === 'free') {
                return [
                    'type' => 'free',
                    'inside' => 0.0,
                    'outside' => 0.0,
                ];
            }

            if ($vType === 'custom') {
                return [
                    'type' => 'custom',
                    'inside' => $variant->custom_delivery_inside !== null ? (float) $variant->custom_delivery_inside : null,
                    'outside' => $variant->custom_delivery_outside !== null ? (float) $variant->custom_delivery_outside : null,
                ];
            }

            if ($variant->product?->free_delivery) {
                return [
                    'type' => 'free',
                    'inside' => 0.0,
                    'outside' => 0.0,
                ];
            }
        } elseif ($this->defaultProduct()?->free_delivery) {
            return [
                'type' => 'free',
                'inside' => 0.0,
                'outside' => 0.0,
            ];
        }

        return [
            'type' => 'default',
            'inside' => null,
            'outside' => null,
        ];
    }

    /**
     * Get a customized checkout label or placeholder with fallback.
     */
    public function checkoutField(string $key, ?string $default = null): ?string
    {
        $val = data_get($this->checkout_fields_config, $key);

        return filled($val) ? (string) $val : $default;
    }

    /**
     * Get configured form fields in specified drag-and-drop order with custom extra fields.
     *
     * @return array<int, array{key: string, type: string, label: string, placeholder: string, required: bool, is_system: bool, enabled: bool}>
     */
    public function resolvedCheckoutFields(): array
    {
        $defaultFields = [
            [
                'key' => 'customer_name',
                'type' => 'text',
                'label' => $this->checkoutField('name_label', 'আপনার নাম'),
                'placeholder' => $this->checkoutField('name_placeholder', 'যেমন: মো: আরিফুল ইসলাম'),
                'required' => true,
                'is_system' => true,
                'enabled' => true,
            ],
            [
                'key' => 'customer_phone',
                'type' => 'tel',
                'label' => $this->checkoutField('phone_label', 'আপনার ফোন নাম্বার'),
                'placeholder' => $this->checkoutField('phone_placeholder', 'যেমন: 017XXXXXXXX'),
                'required' => true,
                'is_system' => true,
                'enabled' => true,
            ],
            [
                'key' => 'customer_address',
                'type' => 'textarea',
                'label' => $this->checkoutField('address_label', 'আপনার সম্পূর্ণ ঠিকানা'),
                'placeholder' => $this->checkoutField('address_placeholder', 'বাড়ি/রোড নং, এলাকা, থানা ও জেলার নাম লিখুন...'),
                'required' => true,
                'is_system' => true,
                'enabled' => true,
            ],
            [
                'key' => 'shipping_option',
                'type' => 'shipping',
                'label' => $this->checkoutField('shipping_label', 'ডেলিভারি এলাকা নির্বাচন করুন'),
                'placeholder' => '',
                'required' => true,
                'is_system' => true,
                'enabled' => true,
            ],
            [
                'key' => 'order_notes',
                'type' => 'text',
                'label' => 'স্পেশাল নোট (ঐচ্ছিক)',
                'placeholder' => $this->checkoutField('notes_placeholder', 'অতিরিক্ত কোনো মন্তব্য বা স্পেশাল নোট (ঐচ্ছিক)'),
                'required' => false,
                'is_system' => true,
                'enabled' => true,
            ],
        ];

        $orderedList = data_get($this->checkout_fields_config, 'fields_builder');

        if (! is_array($orderedList) || empty($orderedList)) {
            return $defaultFields;
        }

        // Return ordered items merged with defaults
        $result = [];
        foreach ($orderedList as $item) {
            if (empty($item['key'])) {
                continue;
            }
            $isSys = ! empty($item['is_system']);
            $result[] = [
                'key' => (string) $item['key'],
                'type' => (string) ($item['type'] ?? 'text'),
                'label' => (string) ($item['label'] ?? ''),
                'placeholder' => (string) ($item['placeholder'] ?? ''),
                'required' => (bool) ($item['required'] ?? false),
                'is_system' => $isSys,
                'enabled' => (bool) ($item['enabled'] ?? true),
            ];
        }

        // Always show Name → Phone → Address in that sequence
        $pinned = ['customer_name', 'customer_phone', 'customer_address'];
        $byKey = [];
        foreach ($result as $field) {
            $byKey[$field['key']] = $field;
        }
        $ordered = [];
        $seen = [];
        foreach ($pinned as $key) {
            if (! empty($byKey[$key])) {
                $ordered[] = $byKey[$key];
                $seen[$key] = true;
            }
        }
        foreach ($result as $field) {
            if (! empty($seen[$field['key']])) {
                continue;
            }
            $ordered[] = $field;
        }

        return $ordered;
    }
}
