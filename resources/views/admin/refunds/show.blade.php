@extends('layouts.admin')

@section('title', 'Refund '.$refund->refund_number)
@section('heading', 'Refund '.$refund->refund_number)
@section('subheading', 'Order #'.$refund->order?->invoice_no)

@section('actions')
    <a href="{{ route('admin.refunds.index') }}" class="btn-secondary !py-2">← List</a>
@endsection

@section('content')
    @php
        $badge = match ($refund->status) {
            'pending' => 'bg-amber-100 text-amber-800',
            'approved' => 'bg-sky-100 text-sky-800',
            'rejected' => 'bg-red-100 text-red-800',
            'processed' => 'bg-emerald-100 text-emerald-800',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="admin-card admin-card-body">
            <h2 class="mb-2 admin-section-title">Refund details</h2>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div><dt class="text-muted">Refund ID</dt><dd class="font-medium">{{ $refund->refund_number }}</dd></div>
                <div>
                    <dt class="text-muted">Status</dt>
                    <dd>
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">
                            {{ $refund->statusLabel() }}
                        </span>
                    </dd>
                </div>
                <div><dt class="text-muted">Amount</dt><dd class="font-semibold text-sage">৳{{ number_format($refund->amount, 2) }}</dd></div>
                <div><dt class="text-muted">Method</dt><dd>{{ $refund->methodLabel() }}</dd></div>
                <div><dt class="text-muted">Date</dt><dd>{{ $refund->created_at?->format('d M Y, h:i A') }}</dd></div>
                <div><dt class="text-muted">Processed at</dt><dd>{{ $refund->processed_at?->format('d M Y, h:i A') ?: '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-muted">Reason</dt><dd class="mt-1">{{ $refund->reason ?: '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-muted">Admin note</dt><dd class="mt-1">{{ $refund->admin_note ?: '—' }}</dd></div>
                @if ($refund->transaction_id)
                    <div><dt class="text-muted">Transaction</dt><dd>{{ $refund->transaction_id }}</dd></div>
                @endif
                @if ($refund->account)
                    <div><dt class="text-muted">Account</dt><dd>{{ $refund->account }} {{ $refund->account_name ? '('.$refund->account_name.')' : '' }}</dd></div>
                @endif
                @if ($refund->processor)
                    <div><dt class="text-muted">Handled by</dt><dd>{{ $refund->processor->name }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="admin-card admin-card-body">
            <h2 class="mb-2 admin-section-title">Customer / Order</h2>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-muted">Customer</dt><dd class="font-medium">{{ $refund->order?->customer_name }}</dd></div>
                <div><dt class="text-muted">Phone</dt><dd>{{ $refund->order?->customer_phone }}</dd></div>
                <div><dt class="text-muted">Address</dt><dd class="whitespace-pre-line">{{ $refund->order?->customer_address }}</dd></div>
                <div>
                    <dt class="text-muted">Order</dt>
                    <dd>
                        <a href="{{ route('admin.orders.show', $refund->order) }}" class="font-medium text-sage hover:underline">
                            #{{ $refund->order?->invoice_no }}
                        </a>
                        · ৳{{ number_format($refund->order?->total ?? 0, 2) }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    @if ($refund->isPending())
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}" class="admin-card admin-card-body">
                @csrf
                <h2 class="mb-2 admin-section-title">Approve</h2>
                <textarea name="admin_note" rows="2" class="field mb-3" placeholder="Optional admin note">{{ old('admin_note', $refund->admin_note) }}</textarea>
                <button type="submit" class="inline-flex rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                    Approve refund
                </button>
            </form>
            <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}" class="admin-card admin-card-body" onsubmit="return confirm('Reject this refund?')">
                @csrf
                <h2 class="mb-2 admin-section-title">Reject</h2>
                <textarea name="admin_note" rows="2" class="field mb-3" placeholder="Optional rejection note">{{ old('admin_note') }}</textarea>
                <button type="submit" class="inline-flex rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Reject refund
                </button>
            </form>
        </div>
    @endif

    @if ($refund->isApproved())
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('admin.refunds.process', $refund) }}" class="admin-card admin-card-body">
                @csrf
                <h2 class="mb-2 admin-section-title">Mark processed</h2>
                <div class="mb-3 grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="transaction_id">Transaction ID</label>
                        <input id="transaction_id" type="text" name="transaction_id" class="field" required value="{{ old('transaction_id') }}">
                    </div>
                    <div>
                        <label class="admin-label" for="method">Method</label>
                        <select id="method" name="method" class="field" required>
                            @foreach (['original_payment' => 'Original payment', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank transfer', 'manual' => 'Manual'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('method', $refund->method) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="account">Account / phone</label>
                        <input id="account" type="text" name="account" class="field" value="{{ old('account', $refund->account) }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="account_name">Account name</label>
                        <input id="account_name" type="text" name="account_name" class="field" value="{{ old('account_name', $refund->account_name) }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="admin_note">Admin note</label>
                        <textarea id="admin_note" name="admin_note" rows="2" class="field">{{ old('admin_note', $refund->admin_note) }}</textarea>
                    </div>
                </div>
                <button type="submit" class="inline-flex rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Mark as processed
                </button>
            </form>
            <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}" class="admin-card admin-card-body" onsubmit="return confirm('Reject this approved refund?')">
                @csrf
                <h2 class="mb-2 admin-section-title">Reject</h2>
                <textarea name="admin_note" rows="2" class="field mb-3" placeholder="Optional rejection note">{{ old('admin_note') }}</textarea>
                <button type="submit" class="inline-flex rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Reject refund
                </button>
            </form>
        </div>
    @endif
@endsection
