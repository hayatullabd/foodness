@extends('layouts.admin')

@section('title', 'Purchase Reports')
@section('heading', 'Purchase Reports / Logs')
@section('subheading', 'Filter purchase history and review totals')

@section('actions')
    <a href="{{ route('admin.purchases.index') }}" class="btn-secondary !py-2">Back to Purchases</a>
@endsection

@section('content')
    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <div class="border border-ink/10 bg-white px-3.5 py-3">
            <p class="text-xs uppercase tracking-wide text-muted">Filtered Total</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($totalAmount, 2) }} ৳</p>
        </div>
        <div class="border border-red-200 bg-white px-3.5 py-3">
            <p class="text-xs uppercase tracking-wide text-red-700">Filtered Due</p>
            <p class="mt-2 text-2xl font-semibold text-red-700">{{ number_format($totalDue, 2) }} ৳</p>
        </div>
    </div>

    <div class="mb-4 admin-card admin-card-body">
        <form method="GET" action="{{ route('admin.purchases.reports') }}" class="grid gap-3 md:grid-cols-5">
            <div>
                <label class="admin-label" for="from">From</label>
                <input id="from" type="date" name="from" value="{{ $from }}" class="field">
            </div>
            <div>
                <label class="admin-label" for="to">To</label>
                <input id="to" type="date" name="to" value="{{ $to }}" class="field">
            </div>
            <div class="md:col-span-2">
                <label class="admin-label" for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" class="field">
                    <option value="">All suppliers</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) $supplierId === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary !py-2.5">Filter</button>
                <a href="{{ route('admin.purchases.reports') }}" class="btn-secondary !py-2.5">Reset</a>
            </div>
        </form>
    </div>

    <div class="border border-ink/10 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist text-muted">
                    <tr>
                        <th class="px-3 py-2 font-medium">Date</th>
                        <th class="px-3 py-2 font-medium">Invoice</th>
                        <th class="px-3 py-2 font-medium">Supplier</th>
                        <th class="px-3 py-2 font-medium">Items / Attributes</th>
                        <th class="px-3 py-2 font-medium">Total</th>
                        <th class="px-3 py-2 font-medium">Paid</th>
                        <th class="px-3 py-2 font-medium">Due</th>
                        <th class="px-3 py-2 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        <tr class="border-t border-ink/5">
                            <td class="px-3 py-2">{{ $purchase->purchase_date?->format('d-M-Y') }}</td>
                            <td class="px-3 py-2 font-medium">{{ $purchase->invoice_no }}</td>
                            <td class="px-3 py-2">{{ $purchase->supplier?->name }}</td>
                            <td class="px-3 py-2">
                                @forelse ($purchase->items as $it)
                                    <div class="text-xs">
                                        <span class="font-medium text-ink">{{ $it->product?->name }}</span>
                                        @if ($it->attributeLabel())
                                            <span class="inline-flex items-center rounded bg-sage/10 px-1.5 py-0.5 text-[11px] font-semibold text-sage">
                                                {{ $it->attributeLabel() }}
                                            </span>
                                        @endif
                                        <span class="text-muted">× {{ $it->quantity }}</span>
                                    </div>
                                @empty
                                    <span class="text-xs text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="px-3 py-2">{{ number_format($purchase->total, 2) }} ৳</td>
                            <td class="px-3 py-2">{{ number_format($purchase->paid_amount, 2) }} ৳</td>
                            <td class="px-3 py-2">
                                @if ($purchase->isPaid())
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Paid</span>
                                @else
                                    <span class="text-red-600">{{ number_format($purchase->due, 2) }} ৳</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.purchases.show', $purchase) }}" class="text-sage hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-muted">No purchases match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($purchases->hasPages())
            <div class="border-t border-ink/10 px-3 py-2">{{ $purchases->links() }}</div>
        @endif
    </div>
@endsection
