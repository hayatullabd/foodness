@extends('layouts.admin')

@section('title', 'Customers')
@section('heading', 'Customers')
@section('subheading', 'Storefront accounts and order history')

@section('content')
    <form method="GET" class="admin-toolbar">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, email, phone" class="field max-w-xs">
        <button type="submit" class="admin-btn-secondary">Search</button>
    </form>

    <div class="admin-card">
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
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-sage hover:underline">
                                    {{ $customer->name }}
                                </a>
                            </td>
                            <td>{{ $customer->email ?: '—' }}</td>
                            <td>{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->orders_count }}</td>
                            <td class="text-muted">{{ $customer->created_at?->format('d M Y') }}</td>
                            @if (auth('admin')->user()?->isSuperAdmin())
                                <td class="text-right">
                                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Are you sure you want to permanently delete customer \'{{ addslashes($customer->name) }}\'? This cannot be undone.')" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 rounded bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-700 transition" title="Delete customer">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth('admin')->user()?->isSuperAdmin() ? 6 : 5 }}" class="py-8 text-center text-muted">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-ink/10 px-3 py-2">{{ $customers->links() }}</div>
    </div>
@endsection
