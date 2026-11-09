<?php

namespace App\Models;

use Database\Factories\RefundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'refund_number',
    'amount',
    'method',
    'status',
    'reason',
    'admin_note',
    'account',
    'account_name',
    'transaction_id',
    'processed_by',
    'processed_at',
])]
class Refund extends Model
{
    /** @use HasFactory<RefundFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PROCESSED = 'processed';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_PROCESSED,
    ];

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'method' => 'original_payment',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeProcessed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_PROCESSED => 'Completed',
            default => str($this->status)->replace('_', ' ')->title()->toString(),
        };
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'original_payment' => 'Original payment',
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'bank' => 'Bank transfer',
            'manual' => 'Manual',
            default => str($this->method)->replace('_', ' ')->title()->toString(),
        };
    }

    public static function generateRefundNumber(): string
    {
        do {
            $number = 'REF-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::query()->where('refund_number', $number)->exists());

        return $number;
    }

    /**
     * Normalize UI aliases (e.g. completed → processed) to a stored status.
     */
    public static function normalizeStatus(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        $status = strtolower($status);

        if ($status === 'completed') {
            return self::STATUS_PROCESSED;
        }

        return in_array($status, self::STATUSES, true) ? $status : null;
    }
}
