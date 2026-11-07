@extends('layouts.admin')

@section('title', $customer->name)
@section('heading', $customer->name)
@section('subheading', $customer->email ?: $customer->phone)

@section('content')
    <div class="grid gap-3 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-1">
            <div class="admin-card admin-card-body text-sm">
                <h2 class="mb-3 font-medium">Profile</h2>
                <p><span class="text-muted">Email:</span> {{ $customer->email ?: '—' }}</p>
                <p class="mt-2"><span class="text-muted">Phone:</span> {{ $customer->phone ?: '—' }}</p>
                <p class="mt-2 whitespace-pre-line"><span class="text-muted">Address:</span><br>{{ $customer->address ?: '—' }}</p>
                <p class="mt-2 text-muted">Joined {{ $customer->created_at?->format('d M Y') }}</p>
            </div>

            <div class="admin-card admin-card-body text-sm">
                <h2 class="mb-3 font-medium">Courier ratio summary</h2>
                @if ($lastFraudOrder)
                    @include('admin.partials.fraud-badge', ['score' => $lastFraudOrder->fraud_score, 'size' => 'md'])
                    <p class="mt-2 text-xs text-muted">
                        From order
                        <a href="{{ route('admin.orders.show', $lastFraudOrder) }}" class="text-sage hover:underline">
                            #{{ $lastFraudOrder->invoice_no }}
                        </a>
                    </p>
                @else
                    <p class="text-muted">No courier ratio on file yet.</p>
                @endif

                <form method="POST" action="{{ route('admin.customers.fraud-check', $customer) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-md bg-teal-600 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-700">
                        Check Ratio
                    </button>
                </form>
            </div>

            @if (auth('admin')->user()?->isSuperAdmin())
                <div class="admin-card admin-card-body text-sm border-red-200 bg-red-50/40">
                    <h2 class="mb-1 font-semibold text-red-900">Danger Zone</h2>
                    <p class="text-xs text-red-700">Permanently delete this customer account. Any associated orders will remain safely stored with preserved shipping records.</p>
                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Are you sure you want to permanently delete customer \'{{ addslashes($customer->name) }}\'? This cannot be undone.')" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 transition shadow-xs">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Delete Customer Account
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="border border-ink/10 bg-white lg:col-span-2">
            <div class="border-b border-ink/10 px-3.5 py-3 font-medium">Orders</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-mist text-muted">
                        <tr>
                            <th class="px-3 py-2 font-medium">Invoice</th>
                            <th class="px-3 py-2 font-medium">Total</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 font-medium">Courier ratio</th>
                            <th class="px-3 py-2 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->orders as $order)
                            <tr class="border-t border-ink/5">
                                <td class="px-3 py-2">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-sage hover:underline">{{ $order->invoice_no }}</a>
                                </td>
                                <td class="px-3 py-2">৳{{ number_format($order->total, 2) }}</td>
                                <td class="px-3 py-2 capitalize">{{ $order->status }}</td>
                                <td class="px-3 py-2">
                                    @include('admin.partials.fraud-badge', ['score' => $order->fraud_score])
                                </td>
                                <td class="px-3 py-2 text-muted">{{ $order->created_at?->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-muted">No orders for this customer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
