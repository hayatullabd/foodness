@extends('layouts.admin')

@php
    use App\Support\Money;
@endphp

@section('title', 'Courier Dashboard')
@section('heading', 'Courier Dashboard')
@section('subheading', 'Balances, API status, and booking stats')

@section('actions')
    <a href="{{ route('admin.courier-panel.index', ['refresh' => 1]) }}" class="admin-btn-secondary">Refresh</a>
    <a href="{{ route('admin.courier-apis.index') }}" class="admin-btn-primary">Delivery Methods</a>
@endsection

@section('content')
    <div class="space-y-2">
        @if ($refreshed)
            <div class="rounded border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] text-emerald-800">
                API data refreshed.
            </div>
        @endif

        {{-- One-line summary --}}
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-md border border-ink/10 bg-white px-2.5 py-1.5 text-[11px]">
            <span class="font-medium text-ink">{{ $summary['active_methods'] }} couriers</span>
            <span class="text-ink/20">|</span>
            <span class="text-muted">{{ $summary['api_ok'] }} ok · {{ $summary['api_failed'] }} issue</span>
            <span class="text-ink/20">|</span>
            <span class="tabular-nums text-ink">
                Balance:
                <span class="font-semibold">{{ $summary['has_live_balance'] ? Money::format($summary['total_balance']) : '—' }}</span>
            </span>
            <span class="text-ink/20">|</span>
            <span class="tabular-nums text-muted">Booked {{ number_format($summary['booked_total']) }}</span>
            <span class="tabular-nums text-muted">Network {{ number_format($summary['in_courier_total'] + $summary['on_the_way_total']) }}</span>
            <span class="tabular-nums text-muted">COD {{ Money::format($summary['cod_open_total']) }}</span>
            <span class="tabular-nums text-muted">Delivered {{ number_format($summary['delivered_total']) }}</span>
        </div>

        @if ($panels === [])
            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5 text-sm text-muted">
                No active courier APIs.
                <a href="{{ route('admin.courier-apis.index', ['create' => 1]) }}" class="ml-1 font-medium text-sage hover:underline">Add method</a>
            </div>
        @else
            <div class="space-y-1.5">
                @foreach ($panels as $panel)
                    <section class="rounded-md border border-ink/10 bg-white px-2.5 py-2">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <h2 class="text-sm font-semibold text-ink">{{ $panel['label'] }}</h2>
                            <span class="rounded bg-mist px-1.5 py-0.5 text-[10px] font-semibold text-ink">{{ $panel['provider_label'] }}</span>
                            @if ($panel['is_preferred'])
                                <span class="rounded bg-sage/10 px-1.5 py-0.5 text-[10px] font-semibold text-sage">Preferred</span>
                            @endif
                            <span @class([
                                'rounded px-1.5 py-0.5 text-[10px] font-semibold',
                                'bg-emerald-50 text-emerald-700' => $panel['api']['ok'],
                                'bg-red-50 text-red-700' => ! $panel['api']['ok'],
                            ])>
                                {{ $panel['api']['ok'] ? 'Connected' : 'Error' }}
                            </span>
                            <span class="min-w-0 flex-1 truncate text-[11px] text-muted">{{ $panel['api']['message'] }}</span>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('admin.orders.index', ['courier' => $panel['id']]) }}" class="admin-btn-secondary !px-2 !py-1 !text-[11px]">Orders</a>
                                <a href="{{ route('admin.courier-apis.index', ['edit' => $panel['id']]) }}" class="admin-btn bg-ink !px-2 !py-1 !text-[11px] text-white hover:bg-ink/90">API</a>
                            </div>
                        </div>

                        <div class="mt-1.5 flex flex-wrap items-stretch gap-1.5">
                            <div class="min-w-[7.5rem] rounded border border-ink/10 bg-mist/50 px-2 py-1">
                                <p class="text-[9px] font-medium uppercase tracking-wide text-muted">Balance</p>
                                <p class="text-sm font-semibold tabular-nums text-ink">
                                    {{ $panel['api']['balance'] !== null ? Money::format($panel['api']['balance']) : 'N/A' }}
                                </p>
                            </div>

                            @foreach ([
                                ['Booked', number_format($panel['stats']['booked'])],
                                ['In courier', number_format($panel['stats']['in_courier'])],
                                ['On way', number_format($panel['stats']['on_the_way'])],
                                ['Delivered', number_format($panel['stats']['delivered'])],
                                ['Open COD', Money::format($panel['stats']['cod_open'])],
                                ['Del. value', Money::format($panel['stats']['delivered_value'])],
                            ] as [$label, $value])
                                <div class="min-w-[4.5rem] flex-1 rounded border border-ink/10 px-2 py-1">
                                    <p class="text-[9px] uppercase tracking-wide text-muted">{{ $label }}</p>
                                    <p class="text-xs font-semibold tabular-nums text-ink">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if ($panel['api']['details'] !== [])
                            <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-0.5 text-[11px] text-muted">
                                @foreach ($panel['api']['details'] as $detail)
                                    <span>
                                        <span class="text-ink/50">{{ $detail['label'] }}:</span>
                                        <span class="font-medium text-ink">{{ $detail['value'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif

        <div class="overflow-hidden rounded-md border border-ink/10 bg-white">
            <div class="flex items-center justify-between gap-2 border-b border-ink/10 px-2.5 py-1.5">
                <h2 class="text-xs font-semibold text-ink">Recent bookings</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-[11px] font-medium text-sage hover:underline">All</a>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table text-[12px]">
                    <thead>
                        <tr>
                            <th class="!px-2 !py-1.5">Invoice</th>
                            <th class="!px-2 !py-1.5">Courier</th>
                            <th class="!px-2 !py-1.5">Tracking</th>
                            <th class="!px-2 !py-1.5">Status</th>
                            <th class="!px-2 !py-1.5">Amount</th>
                            <th class="!px-2 !py-1.5">Synced</th>
                            <th class="!px-2 !py-1.5 text-right">Print</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentBookings as $order)
                            <tr>
                                <td class="!px-2 !py-1.5">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-sage hover:underline">
                                        #{{ $order->invoice_no }}
                                    </a>
                                </td>
                                <td class="!px-2 !py-1.5">{{ $order->courierApi?->displayName() ?: strtoupper((string) $order->courier_provider) }}</td>
                                <td class="!px-2 !py-1.5 tabular-nums text-muted">{{ $order->tracking_number }}</td>
                                <td class="!px-2 !py-1.5">
                                    <span class="admin-badge-pill admin-badge-neutral">{{ $order->statusLabel() }}</span>
                                </td>
                                <td class="!px-2 !py-1.5 font-medium tabular-nums">{{ Money::format($order->total) }}</td>
                                <td class="!px-2 !py-1.5 whitespace-nowrap text-muted">
                                    {{ $order->courier_synced_at?->format('d M, h:i A') ?: $order->updated_at?->format('d M, h:i A') }}
                                </td>
                                <td class="!px-2 !py-1.5 whitespace-nowrap text-right">
                                    <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="text-[11px] font-medium text-indigo-700 hover:underline">Invoice</a>
                                    <span class="text-slate-300 mx-0.5">|</span>
                                    <a href="{{ route('admin.orders.sticker', $order) }}" target="_blank" class="text-[11px] font-medium text-amber-700 hover:underline">Sticker</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="!py-4 text-center text-muted">No courier bookings yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
