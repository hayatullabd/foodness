@extends('layouts.admin')

@section('title', 'Customers')
@section('heading', 'Customers')
@section('subheading', 'Storefront accounts and order history')

@section('content')
    <form method="GET" class="admin-toolbar">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, email, phone" class="field max-w-xs">
        <button type="submit" class="admin-btn-secondary">Search</button>
    </form>

    <div class="admin-card overflow-hidden">
        <div class="admin-card-header">
            <div>
                <h2 class="text-sm font-semibold text-ink">Customers</h2>
                <p class="text-xs text-muted mt-0.5">{{ number_format($customers->total()) }} result{{ $customers->total() === 1 ? '' : 's' }}</p>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Joined</th>
                        @if (auth('admin')->user()?->isSuperAdmin())
                            <th class="text-right">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-semibold text-sage hover:underline">
                                    {{ $customer->name }}
                                </a>
                            </td>
                            <td>{{ $customer->email ?: '—' }}</td>
                            <td class="tabular-nums">{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->orders_count }}</td>
                            <td class="text-muted">{{ $customer->created_at?->format('d M Y') }}</td>
                            @if (auth('admin')->user()?->isSuperAdmin())
                                <td class="text-right">
                                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Are you sure you want to permanently delete customer \'{{ addslashes($customer->name) }}\'? This cannot be undone.')" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn-danger" title="Delete customer">Delete</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth('admin')->user()?->isSuperAdmin() ? 6 : 5 }}" class="admin-empty-cell">
                                <div class="admin-empty">
                                    <p class="admin-empty-title">No customers found</p>
                                    <p class="admin-empty-hint">Customers appear here after storefront signups or orders.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="orders-footer">
            <div class="text-xs text-muted">
                @if ($customers->total())
                    Showing {{ $customers->firstItem() }}–{{ $customers->lastItem() }} of {{ $customers->total() }}
                @endif
            </div>
            <div>{{ $customers->links() }}</div>
        </div>
    </div>
@endsection
