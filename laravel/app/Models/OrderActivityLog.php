<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

#[Fillable([
    'order_id',
    'causer_type',
    'causer_id',
    'causer_name',
    'action',
    'description',
    'changes',
    'ip_address',
])]
class OrderActivityLog extends Model
{
    /**
     * @var array<string, string>
     */
    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Record an action log on an order.
     *
     * @param  array<string, mixed>|null  $changes
     */
    public static function record(
        Order|int $order,
        string $action,
        ?string $description = null,
        ?array $changes = null,
        ?string $causerType = null,
        ?int $causerId = null,
        ?string $causerName = null,
    ): ?self {
        try {
            $orderId = $order instanceof Order ? $order->id : $order;

            if ($causerType === null) {
                if (Auth::guard('admin')->check()) {
                    $admin = Auth::guard('admin')->user();
                    $causerType = 'admin';
                    $causerId = $admin->id;
                    $causerName = $admin->name ?? $admin->email;
                } elseif (Auth::guard('web')->check()) {
                    $user = Auth::guard('web')->user();
                    $causerType = 'user';
                    $causerId = $user->id;
                    $causerName = $user->name ?? $user->email;
                } elseif (Auth::check()) {
                    $u = Auth::user();
                    $causerType = 'user';
                    $causerId = $u->id;
                    $causerName = $u->name ?? $u->email;
                } else {
                    $causerType = 'system';
                    $causerName = 'System';
                }
            }

            return self::create([
                'order_id' => $orderId,
                'causer_type' => $causerType,
                'causer_id' => $causerId,
                'causer_name' => $causerName,
                'action' => $action,
                'description' => $description,
                'changes' => $changes,
                'ip_address' => Request::ip() ?? '127.0.0.1',
            ]);
        } catch (\Throwable) {
            return null;
        }
    }
}
