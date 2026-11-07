@extends('layouts.admin')

@section('title', 'Refund Management')
@section('heading')
    Refund Management
    <span class="ml-2 inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-accent px-2 text-xs font-semibold text-white">{{ $stats['total'] }}</span>
@endsection
@section('subheading', 'View all refund requests, approve and process')

@section('actions')
    <a href="{{ route('admin.refunds.create') }}" class="btn-primary !py-2">Request refund</a>
@endsection

@section('content')
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @php
            $statCards = [
                ['key' => null, 'label' => 'Total', 'count' => $stats['total'], 'border' => 'border-accent/40', 'text' => 'text-ink'],
                ['key' => 'pending', 'label' => 'Pending', 'count' => $stats['pending'], 'border' => 'border-ink/10', 'text' => 'text-amber-700'],
                ['key' => 'approved', 'label' => 'Approved', 'count' => $stats['approved'], 'border' => 'border-ink/10', 'text' => 'text-sky-700'],
                ['key' => 'rejected', 'label' => 'Rejected', 'count' => $stats['rejected'], 'border' => 'border-ink/10', 'text' => 'text-red-700'],
                ['key' => 'completed', 'label' => 'Completed', 'count' => $stats['completed'], 'border' => 'border-ink/10', 'text' => 'text-emerald-700'],
            ];
        @endphp
        @foreach ($statCards as $card)
            <a
                href="{{ route('admin.refunds.index', array_filter(['status' => $card['key'], 'invoice' => $invoice ?: null])) }}"
                @class([
                    'border bg-white px-3.5 py-3 transition hover:border-sage/40',
                    $card['border'],
                    'ring-1 ring-sage/30' => ($currentStatus === null && $card['key'] === null)
                        || ($currentStatus === 'processed' && $card['key'] === 'completed')
                        || ($currentStatus === $card['key']),
                ])
            >
                <p class="admin-stat-label">{{ $card['label'] }}</p>
                <p class="mt-1 text-xl font-semibold {{ $card['text'] }}">{{ $card['count'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="mb-4 admin-card admin-card-body">
        <h2 class="mb-2.5 admin-section-title">Filter</h2>
        <form method="GET" action="{{ route('admin.refunds.index') }}" class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="admin-label" for="invoice">Search Invoice</label>
                <input
                    id="invoice"
                    type="text"
                    name="invoice"
                    value="{{ $invoice }}"
                    placeholder="Invoice number..."
                    class="field"
                >
            </div>
            <div>
                <label class="admin-label" for="status">Status</label>
                <select id="status" name="status" class="field">
                    <option value="">All Statuses</option>
                    <option value="pending" @selected($currentStatus === 'pending')>Pending</option>
                    <option value="approved" @selected($currentStatus === 'approved')>Approved</option>
                    <option value="rejected" @selected($currentStatus === 'rejected')>Rejected</option>
                    <option value="processed" @selected($currentStatus === 'processed')>Completed</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary !py-2.5">Filter</button>
                <a href="{{ route('admin.refunds.index') }}" class="btn-secondary !py-2.5">Reset</a>
            </div>
        </form>
    </div>

    <div class="border border-ink/10 bg-white">
        <div class="flex items-center justify-between border-b border-ink/10 px-3 py-2">
            <h2 class="admin-section-title">Refund List</h2>
            <a href="{{ request()->fullUrl() }}" class="text-xs text-sage hover:underline">Refresh</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist text-muted">
                    <tr>
                        <th class="px-3 py-2 font-medium">#</th>
                        <th class="px-3 py-2 font-medium">Refund</th>
                        <th class="px-3 py-2 font-medium">Customer</th>
                        <th class="px-3 py-2 font-medium">Amount</th>
                        <th class="px-3 py-2 font-medium">Method</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Date</th>
                        <th class="px-3 py-2 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($refunds as $refund)
                        @php
                            $badge = match ($refund->status) {
                                'pending' => 'bg-amber-100 text-amber-800',
                                'approved' => 'bg-sky-100 text-sky-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'processed' => 'bg-emerald-100 text-emerald-800',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr class="border-t border-ink/5">
                            <td class="px-3 py-2 text-muted">{{ $refunds->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.refunds.show', $refund) }}" class="font-medium text-sage hover:underline">
                                    {{ $refund->refund_number }}
                                </a>
                                <div class="text-xs text-muted">
                                    Invoice
                                    <a href="{{ route('admin.orders.show', $refund->order) }}" class="hover:underline">
                                        #{{ $refund->order?->invoice_no }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-medium">{{ $refund->order?->customer_name }}</div>
                                <div class="text-xs text-muted">{{ $refund->order?->customer_phone }}</div>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">৳{{ number_format($refund->amount, 2) }}</td>
                            <td class="px-3 py-2">{{ $refund->methodLabel() }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">
                                    {{ $refund->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-muted whitespace-nowrap">{{ $refund->created_at?->format('d M Y') }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.refunds.show', $refund) }}" class="inline-flex rounded bg-sage px-2 py-1 text-xs font-medium text-white hover:bg-sage/90">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-mist text-muted" aria-hidden="true">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75A1.5 1.5 0 015.25 5.25h4.379a1.5 1.5 0 011.06.44l1.122 1.12a1.5 1.5 0 001.061.44H18.75a1.5 1.5 0 011.5 1.5v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V6.75z" />
                                    </svg>
                                </div>
                                <p class="text-muted">No refund requests found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-ink/10 px-3 py-2">{{ $refunds->links() }}</div>
    </div>
@endsection
