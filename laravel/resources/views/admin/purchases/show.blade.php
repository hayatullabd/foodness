@extends('layouts.admin')

@section('title', 'Purchase '.$purchase->invoice_no)
@section('heading', 'Purchase '.$purchase->invoice_no)
@section('subheading', 'View purchase details and payment history')

@section('actions')
    <a href="{{ route('admin.purchases.index') }}" class="btn-secondary !py-2">Back</a>
    <a href="{{ route('admin.purchases.edit', $purchase) }}" class="btn-primary !py-2">Edit</a>
@endsection

@section('content')
    <div class="grid gap-3 lg:grid-cols-3">
        <div class="space-y-4 admin-card admin-card-body lg:col-span-2">
            <dl class="grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">Supplier</dt>
                    <dd class="font-medium">{{ $purchase->supplier?->name }}</dd>
                    <dd class="text-sm text-muted">{{ $purchase->supplier?->phone }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">Date</dt>
                    <dd class="font-medium">{{ $purchase->purchase_date?->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">Invoice</dt>
                    <dd class="font-medium">{{ $purchase->invoice_no }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">Note</dt>
                    <dd>{{ $purchase->note ?: '—' }}</dd>
                </div>
            </dl>

            <div class="overflow-x-auto border-t border-ink/10 pt-4">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-mist text-muted">
                        <tr>
                            <th class="px-3 py-2 font-medium">Product</th>
                            <th class="px-3 py-2 font-medium">Attributes / Variation</th>
                            <th class="px-3 py-2 font-medium">Qty</th>
                            <th class="px-3 py-2 font-medium">Unit Cost</th>
                            <th class="px-3 py-2 font-medium">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase->items as $item)
                            <tr class="border-t border-ink/5">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-ink">{{ $item->product?->name }}</div>
                                    @if ($item->product?->sku)
                                        <div class="text-xs text-muted">SKU: {{ $item->product->sku }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if ($item->attributeLabel())
                                        <span class="inline-flex items-center rounded-md bg-sage/15 px-2 py-0.5 text-xs font-semibold text-sage">
                                            {{ $item->attributeLabel() }}
                                        </span>
                                    @else
                                        <span class="text-xs text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ $item->quantity }}</td>
                                <td class="px-3 py-2">{{ number_format($item->unit_cost, 2) }} ৳</td>
                                <td class="px-3 py-2">{{ number_format($item->line_total, 2) }} ৳</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="admin-card admin-card-body">
                <h2 class="mb-2 admin-section-title">Totals</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd>{{ number_format($purchase->subtotal, 2) }} ৳</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Discount</dt><dd>{{ number_format($purchase->discount, 2) }} ৳</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd>{{ number_format($purchase->shipping_cost, 2) }} ৳</dd></div>
                    <div class="flex justify-between border-t border-ink/10 pt-2 font-semibold"><dt>Total</dt><dd>{{ number_format($purchase->total, 2) }} ৳</dd></div>
                    <div class="flex justify-between text-emerald-700"><dt>Paid</dt><dd>{{ number_format($purchase->paid_amount, 2) }} ৳</dd></div>
                    <div class="flex justify-between {{ $purchase->isPaid() ? 'text-emerald-700' : 'text-red-600' }}">
                        <dt>Due</dt>
                        <dd>
                            @if ($purchase->isPaid())
                                Paid
                            @else
                                {{ number_format($purchase->due, 2) }} ৳
                            @endif
                        </dd>
                    </div>
                </dl>

                @if (! $purchase->isPaid())
                    <form method="POST" action="{{ route('admin.purchases.pay-due', $purchase) }}" class="mt-4 space-y-3 border-t border-ink/10 pt-4">
                        @csrf
                        <div>
                            <label class="admin-label" for="amount">Pay Due</label>
                            <input id="amount" type="number" name="amount" min="0.01" max="{{ $purchase->due }}" step="0.01" value="{{ $purchase->due }}" class="field" required>
                        </div>
                        <div>
                            <label class="admin-label" for="note">Note</label>
                            <input id="note" type="text" name="note" class="field" placeholder="Optional">
                        </div>
                        <button type="submit" class="btn-primary w-full">Record Payment</button>
                    </form>
                @endif
            </div>

            <div class="admin-card admin-card-body">
                <h2 class="mb-2 admin-section-title">Payments</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($purchase->payments as $payment)
                        <li class="flex justify-between border-b border-ink/5 pb-2">
                            <div>
                                <div class="font-medium">{{ number_format($payment->amount, 2) }} ৳</div>
                                <div class="text-xs text-muted">{{ $payment->paid_at?->format('d M Y H:i') }} · {{ $payment->note ?: 'Payment' }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="text-muted">No payments recorded.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
