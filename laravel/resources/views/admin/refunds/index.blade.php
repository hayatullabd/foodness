@extends('layouts.admin')

@section('title', 'Refund Management')
@section('heading')
    Refund Management
    <span class="ml-2 inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-accent px-2 text-xs font-semibold text-white">{{ $stats['total'] }}</span>
@endsection
@section('subheading', 'View all refund requests, approve and process')

@section('actions')
    <a href="{{ route('admin.refunds.create') }}" class="admin-btn-primary">Request refund</a>
@endsection

@section('content')
    <div class="mb-4 grid gap-3 grid-cols-2 lg:grid-cols-5">
        @php
            $statCards = [
                ['key' => null, 'label' => 'Total', 'count' => $stats['total']],
                ['key' => 'pending', 'label' => 'Pending', 'count' => $stats['pending']],
                ['key' => 'approved', 'label' => 'Approved', 'count' => $stats['approved']],
                ['key' => 'rejected', 'label' => 'Rejected', 'count' => $stats['rejected']],
                ['key' => 'completed', 'label' => 'Completed', 'count' => $stats['completed']],
            ];
        @endphp
        @foreach ($statCards as $card)
            <a
                href="{{ route('admin.refunds.index', array_filter(['status' => $card['key'], 'invoice' => $invoice ?: null])) }}"
                @class([
                    'admin-stat block transition hover:border-sage/40',
                    'ring-1 ring-sage/30 border-sage/30' => ($currentStatus === null && $card['key'] === null)
                        || ($currentStatus === 'processed' && $card['key'] === 'completed')
                        || ($currentStatus === $card['key']),
                ])
            >
                <p class="admin-stat-label">{{ $card['label'] }}</p>
                <p class="admin-stat-value !text-xl">{{ $card['count'] }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.refunds.index') }}" class="admin-toolbar mb-4">
        <input type="text" name="invoice" value="{{ $invoice }}" placeholder="Search invoice…" class="field w-40 sm:w-56">
        <select name="status" class="field w-36" aria-label="Status">
            <option value="">All Statuses</option>
            <option value="pending" @selected($currentStatus === 'pending')>Pending</option>
            <option value="approved" @selected($currentStatus === 'approved')>Approved</option>
            <option value="rejected" @selected($currentStatus === 'rejected')>Rejected</option>
            <option value="processed" @selected($currentStatus === 'processed')>Completed</option>
        </select>
        <button type="submit" class="admin-btn-primary">Filter</button>
        <a href="{{ route('admin.refunds.index') }}" class="admin-btn-secondary">Reset</a>
    </form>

    <div class="admin-card overflow-hidden">
        <div class="admin-card-header">
            <div>
                <h2 class="text-sm font-semibold text-ink">Refund list</h2>
                <p class="text-xs text-muted mt-0.5">{{ number_format($refunds->total()) }} result{{ $refunds->total() === 1 ? '' : 's' }}</p>
            </div>
            <a href="{{ request()->fullUrl() }}" class="text-xs font-semibold text-sage hover:underline">Refresh</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Refund</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($refunds as $refund)
                        @php
                            $badge = match ($refund->status) {
                                'pending' => 'admin-badge-warning',
                                'approved' => 'admin-badge-info',
                                'rejected' => 'admin-badge-danger',
                                'processed' => 'admin-badge-success',
                                default => 'admin-badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td class="text-muted tabular-nums">{{ $refunds->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('admin.refunds.show', $refund) }}" class="font-semibold text-sage hover:underline">
                                    {{ $refund->refund_number }}
                                </a>
                                <div class="text-xs text-muted">
                                    Invoice
                                    <a href="{{ route('admin.orders.show', $refund->order) }}" class="hover:underline">
                                        #{{ $refund->order?->invoice_no }}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="font-medium text-ink">{{ $refund->order?->customer_name }}</div>
                                <div class="text-xs text-muted tabular-nums">{{ $refund->order?->customer_phone }}</div>
                            </td>
                            <td class="whitespace-nowrap font-semibold tabular-nums">৳{{ number_format($refund->amount, 2) }}</td>
                            <td>{{ $refund->methodLabel() }}</td>
                            <td>
                                <span class="admin-badge-pill {{ $badge }}">
                                    {{ $refund->statusLabel() }}
                                </span>
                            </td>
                            <td class="text-muted whitespace-nowrap">{{ $refund->created_at?->format('d M Y') }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.refunds.show', $refund) }}" class="admin-btn-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="admin-empty-cell">
                                <div class="admin-empty">
                                    <p class="admin-empty-title">No refund requests found</p>
                                    <p class="admin-empty-hint">Approved refunds and new requests will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="orders-footer">
            <div class="text-xs text-muted">
                @if ($refunds->total())
                    Showing {{ $refunds->firstItem() }}–{{ $refunds->lastItem() }} of {{ $refunds->total() }}
                @endif
            </div>
            <div>{{ $refunds->links() }}</div>
        </div>
    </div>
@endsection
