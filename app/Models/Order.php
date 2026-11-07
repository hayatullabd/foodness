<?php

namespace App\Models;

use App\Support\StoreSettings;
use Database\Factories\OrderFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'landing_page_id',
    'invoice_no',
    'user_id',
    'customer_name',
    'customer_phone',
    'customer_address',
    'delivery_division',
    'delivery_district',
    'delivery_upazila',
    'subtotal',
    'shipping_charge',
    'discount',
    'total',
    'status',
    'order_status_id',
    'payment_method',
    'payment_status',
    'notes',
    'custom_fields',
    'cancel_reason',
    'admin_notes',
    'ip_address',
    'fraud_score',
    'fraud_report',
    'tracking_number',
    'tracking_url',
    'courier_provider',
    'courier_api_id',
    'courier_synced_at',
    'source',
    'source_platform',
    'landing_url',
    'referrer',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_content',
    'utm_term',
    'fbclid',
    'gclid',
    'ttclid',
    'user_agent',
    'device_id',
    'attribution_json',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /** @var array<string, string> */
    public const CANCEL_REASONS = [
        'Customer cancelled' => 'Customer cancelled (গ্রাহক বাতিল করেছেন)',
        'Customer unreachable / Phone off' => 'Customer unreachable / Phone off (ফোন বন্ধ / ধরেননি)',
        'Fake / Test order' => 'Fake / Test order (ভুয়া / টেস্ট অর্ডার)',
        'Duplicate order' => 'Duplicate order (ডুপ্লিকেট অর্ডার)',
        'Delivery charge / Price issue' => 'Delivery charge / Price issue (ডেলিভারি চার্জ / দাম বেশি মনে হয়েছে)',
        'Customer changed mind' => 'Customer changed mind (মত পরিবর্তন করেছেন)',
        'Out of stock' => 'Out of stock (পণ্য স্টকে নেই)',
        'High return risk' => 'High return risk (উচ্চ রিটার্ন ঝুঁকি)',
        'Other' => 'Other (অন্যান্য)',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'custom_fields' => 'array',
            'fraud_score' => 'decimal:2',
            'fraud_report' => 'array',
            'attribution_json' => 'array',
            'courier_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function courierApi(): BelongsTo
    {
        return $this->belongsTo(CourierApi::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(OrderActivityLog::class)->latest();
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function statusLabel(): string
    {
        return $this->orderStatus?->name ?? str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function isCourierBooked(): bool
    {
        return filled($this->tracking_number);
    }

    public function isEditable(): bool
    {
        return ! $this->isCourierBooked();
    }

    public function isCourier(): bool
    {
        return filled($this->tracking_number)
            || ($this->orderStatus && $this->orderStatus->section === OrderStatus::SECTION_COURIER);
    }

    public function isAllOrders(): bool
    {
        return ! $this->isCourier();
    }

    public function applyStatus(OrderStatus|string $status): void
    {
        $oldStatus = $this->statusLabel();

        if ($status instanceof OrderStatus) {
            $orderStatus = $status;
        } else {
            if ($status === 'confirmed') {
                $orderStatus = OrderStatus::query()
                    ->where('section', OrderStatus::SECTION_COURIER)
                    ->where('slug', 'pending')
                    ->first();
            } else {
                $section = $this->isCourier() ? OrderStatus::SECTION_COURIER : OrderStatus::SECTION_ORDERS;
                $orderStatus = OrderStatus::query()
                    ->where('section', $section)
                    ->where('slug', $status)
                    ->first();
            }

            $orderStatus = $orderStatus ?? OrderStatus::query()->where('slug', $status)->firstOrFail();
        }

        $this->forceFill([
            'status' => $orderStatus->slug,
            'order_status_id' => $orderStatus->id,
        ])->save();

        $newStatus = $orderStatus->name;
        if ($oldStatus !== $newStatus) {
            OrderActivityLog::record(
                $this,
                'status_changed',
                "Status changed from '{$oldStatus}' to '{$newStatus}'",
                ['status' => ['old' => $oldStatus, 'new' => $newStatus]]
            );
        }
    }

    public function isDeletable(?Admin $admin = null): bool
    {
        $admin = $admin ?? auth('admin')->user();

        if (! $admin instanceof Admin) {
            return false;
        }

        if (! $admin->canDeleteOrders()) {
            return false;
        }

        if ($admin->isSuperAdmin()) {
            return true;
        }

        if ($this->isCourier()) {
            return false;
        }

        return $this->status === 'pending';
    }

    /**
     * Generate sequential collision-resistant invoice number (e.g. ORD-261026-0001).
     */
    public static function generateInvoiceNumber(?DateTimeInterface $date = null): string
    {
        $date = $date ?? now();
        $prefix = trim((string) (StoreSettings::get('invoice_prefix', 'ORD') ?: 'ORD'));
        $prefix = preg_replace('/[^A-Za-z0-9_-]+/', '', $prefix) ?: 'ORD';
        $prefix = rtrim($prefix, '-_');
        $dateSegment = $date->format('ymd');
        $basePrefix = "{$prefix}-{$dateSegment}-";

        $query = static::query()->where('invoice_no', 'like', "{$basePrefix}%");

        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $latestInvoice = $query
            ->orderByRaw('LENGTH(invoice_no) DESC, invoice_no DESC')
            ->value('invoice_no');

        $nextSerial = 1;
        if ($latestInvoice && preg_match('/^'.preg_quote($basePrefix, '/').'(\d+)$/', $latestInvoice, $matches)) {
            $nextSerial = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = $basePrefix.str_pad((string) $nextSerial, 4, '0', STR_PAD_LEFT);
            $nextSerial++;
        } while (static::query()->where('invoice_no', $candidate)->exists());

        return $candidate;
    }
}
