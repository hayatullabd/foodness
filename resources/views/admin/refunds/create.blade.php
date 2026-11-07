@extends('layouts.admin')

@section('title', 'Request refund')
@section('heading', 'Request refund')
@section('subheading', 'Create a refund request for an order')

@section('actions')
    <a href="{{ route('admin.refunds.index') }}" class="btn-secondary !py-2">← List</a>
@endsection

@section('content')
    <div class="max-w-2xl admin-card admin-card-body">
        <form method="POST" action="{{ route('admin.refunds.store') }}" class="grid gap-4">
            @csrf

            @if ($order)
                <input type="hidden" name="order_id" value="{{ $order->id }}">
                <div class="border border-ink/10 bg-mist px-3 py-2 text-sm">
                    <p class="font-medium">Order #{{ $order->invoice_no }}</p>
                    <p class="text-muted">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                    <p class="mt-1 font-semibold text-sage">Total ৳{{ number_format($order->total, 2) }}</p>
                </div>
            @else
                <div>
                    <label class="admin-label" for="order_id">Order</label>
                    <select id="order_id" name="order_id" class="field" required>
                        <option value="">Select order</option>
                        @foreach ($orders as $selectable)
                            <option value="{{ $selectable->id }}" @selected((string) old('order_id') === (string) $selectable->id)>
                                #{{ $selectable->invoice_no }} — {{ $selectable->customer_name }} (৳{{ number_format($selectable->total, 2) }})
                            </option>
                        @endforeach
                    </select>
                    @error('order_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div>
                <label class="admin-label" for="amount">Amount</label>
                <input
                    id="amount"
                    type="number"
                    name="amount"
                    step="0.01"
                    min="0.01"
                    value="{{ old('amount', $order?->total) }}"
                    class="field"
                    required
                >
                @error('amount')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="admin-label" for="method">Refund method</label>
                <select id="method" name="method" class="field" required>
                    @foreach (['original_payment' => 'Original payment', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank transfer', 'manual' => 'Manual'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('method', 'original_payment') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="admin-label" for="reason">Reason</label>
                <textarea id="reason" name="reason" rows="3" class="field" placeholder="Why is this refund requested?">{{ old('reason') }}</textarea>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="admin-label" for="account">Account / phone</label>
                    <input id="account" type="text" name="account" value="{{ old('account') }}" class="field">
                </div>
                <div>
                    <label class="admin-label" for="account_name">Account name</label>
                    <input id="account_name" type="text" name="account_name" value="{{ old('account_name') }}" class="field">
                </div>
            </div>

            <div>
                <button type="submit" class="btn-primary">Create refund request</button>
            </div>
        </form>
    </div>
@endsection
