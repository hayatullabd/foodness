@extends('layouts.admin')

@section('title', 'Shipping Charges')
@section('heading', 'Shipping Charges')

@section('actions')
    <a href="{{ route('admin.shipping-charges.create') }}" class="admin-btn-primary text-xs">+ Add Charge</a>
@endsection

@section('content')
    {{-- Free Shipping Threshold Settings Card --}}
    <div class="mb-6 admin-card overflow-hidden">
        <div class="admin-card-header">
            <div>
                <h3 class="text-sm font-semibold text-ink flex items-center gap-2">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                    <span>Free Shipping Offer (Order Amount Threshold)</span>
                </h3>
                <p class="mt-0.5 text-[11px] text-muted">
                    Automatically apply free delivery when a customer's cart subtotal reaches or exceeds this amount. Leave blank to disable.
                </p>
            </div>
            @if (filled($freeShippingThreshold))
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 whitespace-nowrap">
                    Active: ৳{{ number_format((float) $freeShippingThreshold, 0) }}+
                </span>
            @endif
        </div>

        <div class="admin-card-body">
            <form method="POST" action="{{ route('admin.shipping-charges.threshold') }}" class="flex flex-wrap items-center gap-3">
                @csrf
                @method('PUT')
                <div class="inline-flex items-center rounded-lg border border-ink/15 bg-white overflow-hidden shadow-2xs focus-within:border-accent focus-within:ring-1 focus-within:ring-accent w-64">
                    <span class="px-3 py-1.5 bg-mist/60 text-xs font-bold text-muted border-r border-ink/10 select-none">
                        ৳
                    </span>
                    <input
                        type="number"
                        step="1"
                        min="0"
                        name="free_shipping_threshold"
                        value="{{ old('free_shipping_threshold', $freeShippingThreshold ?? '') }}"
                        placeholder="e.g. 2500"
                        class="w-full bg-transparent px-3 py-1.5 text-xs font-medium text-ink outline-none placeholder:text-muted/60"
                    >
                </div>
                <button type="submit" class="admin-btn-primary text-xs">
                    Save Changes
                </button>
                @if (filled($freeShippingThreshold))
                    <button
                        type="submit"
                        name="free_shipping_threshold"
                        value=""
                        class="text-xs text-rose-600 hover:underline cursor-pointer ml-1"
                        onclick="return confirm('Are you sure you want to disable the free shipping offer?')"
                    >
                        Disable Offer
                    </button>
                @endif
            </form>
        </div>
    </div>

    {{-- Shipping Charges List --}}
    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist text-muted border-b border-ink/10">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold text-xs">Name</th>
                        <th class="px-4 py-2.5 font-semibold text-xs">Amount</th>
                        <th class="px-4 py-2.5 font-semibold text-xs">Status</th>
                        <th class="px-4 py-2.5 font-semibold text-xs text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @forelse ($shippingCharges as $charge)
                        <tr class="hover:bg-mist/30 transition-colors">
                            <td class="px-4 py-3 font-medium text-ink">{{ $charge->name }}</td>
                            <td class="px-4 py-3 font-semibold text-ink">৳{{ number_format($charge->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                @if ($charge->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-300">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.shipping-charges.edit', $charge) }}" class="admin-btn-secondary text-xs !py-1 !px-2.5">Edit</a>
                                <form method="POST" action="{{ route('admin.shipping-charges.destroy', $charge) }}" class="ml-1.5 inline" onsubmit="return confirm('Delete this shipping charge?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-btn-danger text-xs !py-1 !px-2.5">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-muted text-xs">No shipping charges found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shippingCharges->hasPages())
            <div class="border-t border-ink/10 px-4 py-2.5">{{ $shippingCharges->links() }}</div>
        @endif
    </div>
@endsection
