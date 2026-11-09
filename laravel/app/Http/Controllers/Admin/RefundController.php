<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(Request $request): View
    {
        $status = Refund::normalizeStatus($request->string('status')->toString() ?: null);

        $refunds = Refund::query()
            ->with('order')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->filled('invoice'), function ($query) use ($request): void {
                $term = '%'.$request->string('invoice')->toString().'%';
                $query->whereHas('order', function ($orderQuery) use ($term): void {
                    $orderQuery->where('invoice_no', 'like', $term);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusCounts = Refund::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total' => (int) $statusCounts->sum(),
            'pending' => (int) ($statusCounts[Refund::STATUS_PENDING] ?? 0),
            'approved' => (int) ($statusCounts[Refund::STATUS_APPROVED] ?? 0),
            'rejected' => (int) ($statusCounts[Refund::STATUS_REJECTED] ?? 0),
            'completed' => (int) ($statusCounts[Refund::STATUS_PROCESSED] ?? 0),
        ];

        return view('admin.refunds.index', [
            'refunds' => $refunds,
            'stats' => $stats,
            'currentStatus' => $status,
            'invoice' => $request->string('invoice')->toString(),
        ]);
    }

    public function create(Request $request): View
    {
        $order = null;

        if ($request->filled('order_id')) {
            $order = Order::query()->findOrFail($request->integer('order_id'));
        }

        return view('admin.refunds.create', [
            'order' => $order,
            'orders' => $order ? collect() : Order::query()->latest()->limit(50)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', Rule::in(['original_payment', 'bkash', 'nagad', 'bank', 'manual'])],
            'reason' => ['nullable', 'string', 'max:2000'],
            'account' => ['nullable', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
        ]);

        $order = Order::query()->findOrFail($validated['order_id']);

        if ((float) $validated['amount'] > (float) $order->total) {
            return back()
                ->withInput()
                ->with('error', 'Refund amount cannot exceed the order total.');
        }

        $hasOpenRefund = Refund::query()
            ->where('order_id', $order->id)
            ->whereIn('status', [Refund::STATUS_PENDING, Refund::STATUS_APPROVED])
            ->exists();

        if ($hasOpenRefund) {
            return back()
                ->withInput()
                ->with('error', 'This order already has an open refund request.');
        }

        $refund = Refund::query()->create([
            'order_id' => $order->id,
            'refund_number' => Refund::generateRefundNumber(),
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reason' => $validated['reason'] ?? null,
            'account' => $validated['account'] ?? null,
            'account_name' => $validated['account_name'] ?? null,
            'status' => Refund::STATUS_PENDING,
        ]);

        return redirect()
            ->route('admin.refunds.show', $refund)
            ->with('success', 'Refund request created.');
    }

    public function show(Refund $refund): View
    {
        $refund->load(['order.items', 'processor']);

        return view('admin.refunds.show', [
            'refund' => $refund,
        ]);
    }

    public function approve(Request $request, Refund $refund): RedirectResponse
    {
        if (! $refund->isPending()) {
            return back()->with('error', 'Only pending refunds can be approved.');
        }

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $refund->update([
            'status' => Refund::STATUS_APPROVED,
            'admin_note' => $validated['admin_note'] ?? $refund->admin_note,
            'processed_by' => $request->user('admin')?->id,
        ]);

        return back()->with('success', 'Refund approved.');
    }

    public function reject(Request $request, Refund $refund): RedirectResponse
    {
        if (! $refund->isPending() && ! $refund->isApproved()) {
            return back()->with('error', 'This refund cannot be rejected.');
        }

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $refund->update([
            'status' => Refund::STATUS_REJECTED,
            'admin_note' => $validated['admin_note'] ?? $refund->admin_note,
            'processed_by' => $request->user('admin')?->id,
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Refund rejected.');
    }

    public function process(Request $request, Refund $refund): RedirectResponse
    {
        if (! $refund->isApproved()) {
            return back()->with('error', 'Only approved refunds can be marked as processed.');
        }

        $validated = $request->validate([
            'transaction_id' => ['required', 'string', 'max:255'],
            'method' => ['required', 'string', Rule::in(['original_payment', 'bkash', 'nagad', 'bank', 'manual'])],
            'account' => ['nullable', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $refund->update([
            'status' => Refund::STATUS_PROCESSED,
            'transaction_id' => $validated['transaction_id'],
            'method' => $validated['method'],
            'account' => $validated['account'] ?? $refund->account,
            'account_name' => $validated['account_name'] ?? $refund->account_name,
            'admin_note' => $validated['admin_note'] ?? $refund->admin_note,
            'processed_by' => $request->user('admin')?->id,
            'processed_at' => now(),
        ]);

        $this->syncOrderAfterProcessedRefund($refund->fresh(['order']));

        return back()->with('success', 'Refund marked as processed. Order status updated to Refunded.');
    }

    /**
     * Processed refunds move the order out of Delivered/Completed so revenue and status tabs stay accurate.
     */
    private function syncOrderAfterProcessedRefund(Refund $refund): void
    {
        $order = $refund->order;

        if ($order === null) {
            $order = Order::query()->find($refund->order_id);
        }

        if ($order === null) {
            return;
        }

        $order->applyStatus('refunded');
        $order->forceFill([
            'payment_status' => 'refunded',
        ])->save();
    }
}
