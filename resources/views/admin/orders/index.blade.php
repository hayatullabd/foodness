@extends('layouts.admin')

@php
    use App\Support\Money;

    $isIncompleteTab = ($activeTab ?? 'list') === 'incomplete';
@endphp

@section('title', $isIncompleteTab ? 'Incomplete Orders' : (($currentSection ?? 'orders') === 'courier' ? 'Courier Orders' : 'All Orders'))
@section('heading', $isIncompleteTab ? 'Incomplete Orders' : (($currentSection ?? 'orders') === 'courier' ? 'Courier Orders' : 'All Orders'))
@section('subheading', $isIncompleteTab
    ? 'Carts where checkout was not completed — tap a row for customer info and products'
    : ($currentStatus ? ucfirst(str_replace('_', ' ', $currentStatus)).' orders' : 'Manage all orders'))

@section('actions')
    <a href="{{ route('admin.orders.create') }}" class="admin-btn-primary inline-flex items-center gap-1.5 shadow-xs" title="Create a manual order">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span>Create Order</span>
    </a>
@endsection

@section('content')
    @php
        $queryBase = array_filter([
            'section' => ($currentSection ?? 'orders') !== 'orders' ? $currentSection : null,
            'q' => request('q'),
            'tracking' => request('tracking'),
            'platform' => ($currentPlatform ?? request('platform')) ?: null,
            'courier' => ($currentCourier ?? request('courier')) ?: null,
            'tab' => in_array($activeTab, ['list', 'incomplete'], true) ? null : $activeTab,
        ], fn ($value) => $value !== null && $value !== '');
        $isOrderStatusTab = ! $isIncompleteTab && $activeTab !== 'fraud';
    @endphp

    {{-- Section Toggle Pills: All Orders vs Courier --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.orders.index', ['section' => 'orders']) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-xs font-semibold shadow-xs transition',
                    'bg-ink text-white ring-1 ring-ink' => ! $isIncompleteTab && ($currentSection ?? 'orders') === 'orders',
                    'bg-white text-muted ring-1 ring-ink/10 hover:text-ink hover:ring-ink/20' => $isIncompleteTab || ($currentSection ?? 'orders') !== 'orders',
                ])
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                <span>All Orders</span>
            </a>

            <a
                href="{{ route('admin.orders.index', ['section' => 'courier']) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-xs font-semibold shadow-xs transition',
                    'bg-ink text-white ring-1 ring-ink' => ! $isIncompleteTab && ($currentSection ?? 'orders') === 'courier',
                    'bg-white text-muted ring-1 ring-ink/10 hover:text-ink hover:ring-ink/20' => $isIncompleteTab || ($currentSection ?? 'orders') !== 'courier',
                ])
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75m0 3.75a1.125 1.125 0 01-1.125-1.125H5.625A1.125 1.125 0 004.5 3.75v10.5" />
                </svg>
                <span>Courier</span>
            </a>

            <a
                href="{{ route('admin.orders.index', ['tab' => 'incomplete']) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-xs font-semibold shadow-xs transition',
                    'bg-ink text-white ring-1 ring-ink' => $isIncompleteTab,
                    'bg-white text-muted ring-1 ring-ink/10 hover:text-ink hover:ring-ink/20' => ! $isIncompleteTab,
                ])
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Incomplete Order</span>
            </a>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('admin.orders.create') }}" class="admin-btn-primary" title="Create a manual order">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Create Order</span>
            </a>
            <button type="button" id="export-orders-btn" class="admin-btn-secondary" title="Export orders (Selected or Filtered/All)">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span id="export-orders-label">Export Orders</span>
            </button>
            <button type="button" onclick="document.getElementById('order-import-modal').classList.remove('hidden')" class="admin-btn-secondary" title="Import orders from CSV">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Import Orders
            </button>
        </div>
    </div>

    {{-- Sub-tabs with counts for active section --}}
    @unless ($isIncompleteTab)
    <div class="mb-3 flex gap-1 overflow-x-auto border-b border-ink/10 pb-px text-sm">
        @foreach ($sectionTabs as $tab)
            @php
                $isTabActive = ! $isIncompleteTab && (
                    ($tab['slug'] === '' && ! request()->filled('status'))
                    || (request('status') === $tab['slug'])
                );
                $tabUrl = route('admin.orders.index', array_filter(array_merge($queryBase, [
                    'section' => $currentSection,
                    'status' => $tab['slug'] !== '' ? $tab['slug'] : null,
                    'tab' => null,
                ])));
            @endphp
            <a
                href="{{ $tabUrl }}"
                @class([
                    'shrink-0 border-b-2 px-2.5 pb-2 text-xs font-medium whitespace-nowrap transition',
                    'border-sage text-sage font-semibold' => $isTabActive,
                    'border-transparent text-muted hover:text-ink' => ! $isTabActive,
                ])
            >
                {{ $tab['label'] }}
                <span class="ml-1 rounded-full bg-mist px-1.5 py-0.5 text-[10px] font-semibold text-ink">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </div>
    @endunless

    @if ($isIncompleteTab)
        @include('admin.incomplete-orders._panel', [
            'incompleteOrders' => $incompleteOrders,
            'ourRecordsByPhone' => $ourRecordsByPhone ?? [],
        ])
    @else
        @if (($currentSection ?? '') === 'courier' && ! empty($courierSections))
            {{-- Per-courier booking sections --}}
            <div class="mb-2.5 flex gap-1.5 overflow-x-auto pb-0.5">
                <a
                    href="{{ route('admin.orders.index', array_filter(array_merge($queryBase, ['courier' => null]))) }}"
                    @class([
                        'shrink-0 rounded-md border px-2.5 py-1.5 text-xs transition',
                        'border-sage bg-sage/10 font-semibold text-sage' => ($currentCourier ?? '') === '',
                        'border-ink/10 bg-white text-muted hover:border-ink/20 hover:text-ink' => ($currentCourier ?? '') !== '',
                    ])
                >
                    All couriers
                    <span class="ml-1 tabular-nums text-[10px] opacity-80">{{ $courierBookedTotal }}</span>
                </a>

                @foreach ($courierSections as $courierSection)
                    <a
                        href="{{ route('admin.orders.index', array_filter(array_merge($queryBase, ['courier' => $courierSection['id'], 'tab' => null]))) }}"
                        @class([
                            'shrink-0 rounded-md border px-2.5 py-1.5 text-xs transition',
                            'border-ink bg-ink text-white' => (string) ($currentCourier ?? '') === (string) $courierSection['id'],
                            'border-ink/10 bg-white text-ink hover:border-ink/25' => (string) ($currentCourier ?? '') !== (string) $courierSection['id'],
                        ])
                    >
                        <span class="font-medium">{{ $courierSection['label'] }}</span>
                        <span @class([
                            'ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-semibold tabular-nums',
                            'bg-white/20 text-white' => (string) ($currentCourier ?? '') === (string) $courierSection['id'],
                            'bg-mist text-ink' => (string) ($currentCourier ?? '') !== (string) $courierSection['id'],
                        ])>{{ $courierSection['booked_count'] }}</span>
                    </a>
                @endforeach

            </div>
        @endif

        {{-- One-line compact toolbar --}}
        <div class="orders-toolbar mb-2.5 flex items-center gap-1 overflow-x-auto pb-0.5">
            <form method="GET" id="orders-filter-form" class="flex shrink-0 items-center gap-1">
                <input type="hidden" name="section" value="{{ $currentSection ?? 'orders' }}">
                @if ($currentStatus)
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                @if ($activeTab !== 'list')
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                @endif
                @if (! empty($currentCourier))
                    <input type="hidden" name="courier" value="{{ $currentCourier }}">
                @endif
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Filter…" class="field w-28">
                <select name="tracking" class="field w-[6.5rem]" aria-label="Tracking">
                    <option value="">Tracking</option>
                    <option value="with" @selected(request('tracking') === 'with')>Has tracking</option>
                    <option value="without" @selected(request('tracking') === 'without')>No tracking</option>
                </select>
                <select name="platform" class="field w-[6.5rem]" aria-label="Platform">
                    <option value="">Platform</option>
                    @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'google' => 'Google', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'direct' => 'Direct', 'referral' => 'Referral'] as $value => $label)
                        <option value="{{ $value }}" @selected(($currentPlatform ?? request('platform')) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="per_page" class="field w-[5.5rem] font-medium text-slate-700" aria-label="Rows per page" onchange="this.form.submit()">
                    @foreach ([50, 100, 200, 500, 1000] as $size)
                        <option value="{{ $size }}" @selected((int) ($perPage ?? 50) === $size)>{{ $size }}/p</option>
                    @endforeach
                </select>
                <button type="submit" class="admin-btn bg-sage text-white hover:bg-sage/90">Go</button>
            </form>

            <span class="mx-0.5 h-4 w-px shrink-0 bg-ink/15" aria-hidden="true"></span>

            <form method="POST" action="{{ route('admin.orders.bulk') }}" id="orders-bulk-form" class="flex shrink-0 items-center gap-1">
                @csrf
                <input type="hidden" name="section" value="{{ $currentSection ?? 'orders' }}">
                <input type="hidden" name="cancel_reason" id="bulk-cancel-reason-input" value="">
                <select name="status" id="bulk-status-select" class="field w-[7.75rem]" aria-label="Bulk status">
                    <option value="">{{ ($currentSection ?? '') === 'courier' ? 'Courier Status…' : 'Order Status…' }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->slug }}">{{ $status->name }}</option>
                    @endforeach
                </select>
                <button type="submit" name="action" value="status" id="bulk-status-btn" class="admin-btn bg-emerald-600 text-white hover:bg-emerald-700">Assign</button>
                @if (auth('admin')->user()?->canDeleteOrders())
                    <button type="submit" name="action" value="delete" class="admin-btn-danger" onclick="return confirm('Delete selected orders?')">Delete</button>
                @endif

                @if (($currentSection ?? 'orders') === 'courier' && ! empty($courierProviders))
                    <span class="mx-0.5 h-4 w-px shrink-0 bg-ink/15" aria-hidden="true"></span>
                    <select name="courier_api_id" class="field w-28" aria-label="Courier">
                        <option value="">Courier…</option>
                        @foreach ($courierProviders as $courier)
                            <option value="{{ $courier['id'] }}">{{ $courier['label'] }}</option>
                        @endforeach
                    </select>
                    <label class="inline-flex items-center gap-0.5 text-[10px] text-muted" title="Resend even if tracking exists">
                        <input type="checkbox" name="force" value="1" class="h-3 w-3 rounded border-ink/20">
                        Force
                    </label>
                    <button type="submit" name="action" value="send_to_courier" class="admin-btn bg-ink text-white hover:bg-ink/90">Courier Entry</button>
                @endif

                <span class="mx-0.5 h-4 w-px shrink-0 bg-ink/15" aria-hidden="true"></span>
                <button type="submit" name="action" value="print" formtarget="_blank" class="admin-btn bg-indigo-600 text-white hover:bg-indigo-700" title="Print selected orders">
                    <svg class="h-3.5 w-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-table-wrap">
                <table class="admin-table text-[13px]">
                    <thead>
                        <tr>
                            <th class="w-8 px-2 py-2">
                                <input type="checkbox" id="select-all-orders" class="rounded border-ink/20">
                            </th>
                            <th class="px-2 py-2">Created At</th>
                            <th class="px-2 py-2">Invoice</th>
                            <th class="px-2 py-2">Customer</th>
                            <th class="px-2 py-2">Note</th>
                            <th class="px-2 py-2">Order Items</th>
                            <th class="px-2 py-2">Success Rate</th>
                            <th class="px-2 py-2">Amount</th>
                            <th class="px-2 py-2">Tracking</th>
                            <th class="px-2 py-2">Status</th>
                            <th class="px-2 py-2">Site</th>
                            <th class="px-2 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $noteText = trim((string) ($order->admin_notes ?: $order->notes ?: ''));
                                $badge = match ($order->status) {
                                    'pending', 'unpaid', 'advance_payment', 'preorder' => 'admin-badge-warning',
                                    'approved', 'confirmed', 'incomplete_to_confirmed' => 'admin-badge-success',
                                    'processing' => 'admin-badge-info',
                                    'rts' => 'admin-badge-info bg-purple-50 text-purple-700 border-purple-200',
                                    'shipped', 'on_the_way', 'in_courier' => 'admin-badge-info',
                                    'delivered', 'completed' => 'admin-badge-success',
                                    'partial' => 'admin-badge-warning bg-amber-50 text-amber-800 border-amber-300',
                                    'on_hold' => 'admin-badge-warning',
                                    'no_response' => 'admin-badge-neutral',
                                    'pending_return' => 'admin-badge-warning bg-orange-50 text-orange-800 border-orange-200',
                                    'refunded', 'returned', 'courier_returned' => 'admin-badge-warning',
                                    'cancelled' => 'admin-badge-danger',
                                    'lost' => 'admin-badge-danger bg-red-50 text-red-800 border-red-300',
                                    default => 'admin-badge-neutral',
                                };
                            @endphp
                            <tr class="align-top">
                                <td class="px-2 py-3">
                                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" form="orders-bulk-form" class="order-checkbox rounded border-ink/20">
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap">
                                    <div class="font-medium text-ink">{{ $order->created_at?->format('d M, h:i a') }}</div>
                                    <div class="mt-0.5 text-[11px] text-muted">ID: {{ $order->id }}</div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-sage hover:underline">
                                        #{{ $order->invoice_no }}
                                    </a>
                                </td>
                                <td class="px-2 py-3 min-w-[12rem]">
                                    <div class="font-medium tabular-nums text-ink">{{ $order->customer_phone ?: '—' }}</div>
                                    <div class="mt-0.5 font-medium text-ink">{{ $order->customer_name }}</div>
                                    <div class="mt-0.5 max-w-[14rem] truncate text-[11px] text-muted" title="{{ $order->customer_address }}">
                                        {{ $order->customer_address ?: '—' }}
                                    </div>
                                </td>
                                <td class="px-2 py-3 min-w-[8rem]">
                                    <div class="text-[11px] text-muted">Updated {{ $order->updated_at?->diffForHumans() }}</div>
                                    @php
                                        $latestLog = $order->activityLogs->first();
                                    @endphp
                                    @if ($latestLog)
                                        <div class="mt-0.5 max-w-[11rem] truncate text-[11px] font-medium text-sage" title="{{ $latestLog->causer_name }}: {{ $latestLog->description }} ({{ $latestLog->created_at?->format('d M, h:i a') }})">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-sage mr-0.5"></span>
                                            <span class="font-bold text-ink">{{ $latestLog->causer_name }}:</span> {{ $latestLog->description }}
                                        </div>
                                    @endif
                                    <div class="mt-0.5 max-w-[10rem] truncate text-xs text-ink" title="{{ $noteText }}">
                                        {{ $noteText !== '' ? $noteText : '—' }}
                                    </div>
                                </td>
                                <td class="px-2 py-3">
                                    <div class="space-y-1.5">
                                        @forelse ($order->items as $item)
                                            @php
                                                $imagePath = $item->product?->primaryImagePath();
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <div class="h-9 w-9 shrink-0 overflow-hidden rounded border border-ink/10 bg-mist">
                                                    @if ($imagePath)
                                                        <img src="{{ asset('storage/'.$imagePath) }}" alt="" class="h-full w-full object-cover" onerror="this.style.opacity='0.3'">
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="truncate font-medium text-ink" title="{{ $item->product_name }}">{{ $item->product_name }}</div>
                                                    <div class="text-[11px] text-muted">{{ $item->qty }}x {{ Money::format($item->price) }}</div>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-xs text-muted">No items</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td
                                    class="px-2 py-3"
                                    data-ratio-cell
                                    @if ($order->fraud_score === null && blank($order->fraud_report) && filled($order->customer_phone))
                                        data-ratio-pending
                                        data-ratio-url="{{ route('admin.orders.fraud-check', $order) }}"
                                    @endif
                                >
                                    @include('admin.partials.order-success-rate', [
                                        'score' => $order->fraud_score,
                                        'fraudReport' => $order->fraud_report,
                                    ])
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap">
                                    <div class="font-medium text-ink">{{ Money::format($order->total) }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-1">
                                        @if ($order->payment_status === 'paid')
                                            <span class="inline-flex items-center rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                Paid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                                Unpaid
                                            </span>
                                        @endif
                                        <span class="text-[10px] font-medium uppercase text-muted">
                                            {{ $order->payment_method === 'sslcommerz' ? 'SSL' : strtoupper($order->payment_method ?? 'COD') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-2 py-3">
                                    @if ($order->tracking_number)
                                        <div class="text-[11px] font-medium text-ink">
                                            {{ $order->courierApi?->displayName() ?: strtoupper((string) ($order->courier_provider ?: 'Courier')) }}
                                        </div>
                                        <div class="mt-0.5 text-[11px] text-muted">
                                            @if ($order->tracking_url)
                                                <a href="{{ $order->tracking_url }}" target="_blank" class="text-sky-700 hover:underline">{{ $order->tracking_number }}</a>
                                            @else
                                                {{ $order->tracking_number }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="admin-badge admin-badge-neutral">Not booked</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3">
                                    <span class="admin-badge-pill {{ $badge }}">
                                        {{ $order->statusLabel() }}
                                    </span>
                                    @if ($order->status === 'cancelled' && $order->cancel_reason)
                                        <div class="mt-0.5 max-w-[10rem] truncate text-[10px] font-medium text-red-600" title="{{ $order->cancel_reason }}">
                                            {{ $order->cancel_reason }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-2 py-3">
                                    @include('admin.partials.platform-badge', [
                                        'platform' => $order->source_platform ?: 'direct',
                                    ])
                                </td>
                                <td class="px-2 py-3">
                                    <div class="admin-table-actions flex-col items-stretch">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="admin-btn bg-sky-600 text-white hover:bg-sky-700">
                                            Open
                                        </a>
                                        <a href="{{ route('admin.orders.print', $order) }}" target="_blank" class="admin-btn bg-indigo-50 !px-1 text-center text-xs font-semibold text-indigo-700 hover:bg-indigo-100" title="Print (Invoice / Sticker)">
                                            <svg class="h-3 w-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            Print
                                        </a>
                                        <form method="POST" action="{{ route('admin.orders.fraud-check', $order) }}">
                                            @csrf
                                            <button type="submit" class="admin-btn w-full bg-teal-600 text-white hover:bg-teal-700">Check Ratio</button>
                                        </form>
                                        @if ($order->isDeletable(auth('admin')->user()))
                                            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="admin-btn-danger w-full">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="py-8 text-center text-muted">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-ink/10 px-3 py-2 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-xs text-muted">
                    Showing <span class="font-semibold text-ink">{{ $orders->firstItem() ?? 0 }}</span> to <span class="font-semibold text-ink">{{ $orders->lastItem() ?? 0 }}</span> of <span class="font-semibold text-ink">{{ $orders->total() }}</span> orders
                    @if ($orders->total() > 0)
                        (<span class="font-medium text-slate-600">{{ $orders->perPage() }}</span> per page)
                    @endif
                </div>
                <div>{{ $orders->links() }}</div>
            </div>
        </div>

        {{-- Bulk Cancel Reason Modal --}}
        <div id="bulk-cancel-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink/50 p-4">
            <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
                <div class="mb-3 flex items-center justify-between border-b border-ink/10 pb-2">
                    <h3 class="font-semibold text-ink">Select Cancellation Reason</h3>
                    <button type="button" onclick="closeBulkCancelModal()" class="text-lg font-bold leading-none text-muted hover:text-ink">&times;</button>
                </div>
                <p class="mb-3 text-xs text-muted">
                    A cancellation reason is required to cancel selected orders in All Orders.
                </p>
                <div class="space-y-3">
                    <div>
                        <label class="admin-label" for="modal-bulk-cancel-reason">Reason <span class="text-red-500">*</span></label>
                        <select id="modal-bulk-cancel-reason" class="field w-full text-xs">
                            <option value="">Select reason…</option>
                            @foreach (\App\Models\Order::CANCEL_REASONS as $reasonKey => $reasonLabel)
                                <option value="{{ $reasonKey }}">{{ $reasonLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="modal-bulk-custom-reason-box" class="hidden">
                        <label class="admin-label" for="modal-bulk-custom-reason">Custom Reason Note</label>
                        <input type="text" id="modal-bulk-custom-reason" class="field w-full text-xs" placeholder="Specify custom reason…">
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="closeBulkCancelModal()" class="admin-btn bg-mist text-ink hover:bg-ink/10">Back</button>
                    <button type="button" id="modal-bulk-cancel-confirm-btn" class="admin-btn bg-red-600 text-white hover:bg-red-700">Cancel Orders</button>
                </div>
            </div>
        </div>

        <script>
            (() => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const queue = Array.from(document.querySelectorAll('[data-ratio-pending]')).slice(0, 50);
                let active = 0;

                const paint = (cell, html, fallback) => {
                    cell.innerHTML = html || `<span class="text-xs text-muted">${fallback}</span>`;
                    cell.removeAttribute('data-ratio-pending');
                };

                const pump = () => {
                    while (active < 2 && queue.length > 0) {
                        const cell = queue.shift();
                        if (!cell?.dataset.ratioUrl) {
                            continue;
                        }
                        active += 1;
                        cell.innerHTML = '<span class="text-xs font-medium text-teal-700">Checking...</span>';
                        fetch(cell.dataset.ratioUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                            },
                        })
                            .then(async (response) => ({
                                ok: response.ok,
                                data: await response.json().catch(() => null),
                            }))
                            .then(({ ok, data }) => {
                                paint(cell, ok ? data?.html : null, 'Not checked');
                            })
                            .catch(() => paint(cell, null, 'Not checked'))
                            .finally(() => {
                                active -= 1;
                                pump();
                            });
                    }
                };

                pump();

                document.addEventListener('admin:form-saved', (event) => {
                    const form = event.detail?.form;
                    if (!form?.action?.includes('/fraud-check')) {
                        return;
                    }
                    const cell = form.closest('tr')?.querySelector('[data-ratio-cell]');
                    if (cell && event.detail.data?.html) {
                        paint(cell, event.detail.data.html, 'Not checked');
                    }
                });
            })();

            const orderCheckboxes = () => document.querySelectorAll('.order-checkbox');
            const selectAllOrders = document.getElementById('select-all-orders');
            const exportOrdersBtn = document.getElementById('export-orders-btn');
            const exportOrdersLabel = document.getElementById('export-orders-label');

            const updateExportBtnState = () => {
                const checked = document.querySelectorAll('.order-checkbox:checked');
                if (checked.length > 0) {
                    exportOrdersLabel.textContent = `Export Selected (${checked.length})`;
                } else {
                    exportOrdersLabel.textContent = 'Export Orders';
                }
            };

            selectAllOrders?.addEventListener('change', function (e) {
                orderCheckboxes().forEach((el) => { el.checked = e.target.checked; });
                updateExportBtnState();
            });

            orderCheckboxes().forEach((el) => {
                el.addEventListener('change', updateExportBtnState);
            });

            exportOrdersBtn?.addEventListener('click', function () {
                const checked = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
                const currentParams = new URLSearchParams(window.location.search);

                if (checked.length > 0) {
                    currentParams.set('order_ids', checked.join(','));
                } else {
                    currentParams.delete('order_ids');
                }

                const exportUrl = '{{ route("admin.orders.export") }}?' + currentParams.toString();
                window.location.href = exportUrl;
            });

            document.querySelectorAll('[data-copy]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const value = button.getAttribute('data-copy') || '';
                    try {
                        await navigator.clipboard.writeText(value);
                        button.title = 'Copied';
                        setTimeout(() => { button.title = 'Copy phone'; }, 1200);
                    } catch (error) {
                        // ignore clipboard failures
                    }
                });
            });

            const bulkForm = document.getElementById('orders-bulk-form');
            const bulkStatusSelect = document.getElementById('bulk-status-select');
            const bulkCancelReasonInput = document.getElementById('bulk-cancel-reason-input');
            const bulkCancelModal = document.getElementById('bulk-cancel-modal');
            const modalReasonSelect = document.getElementById('modal-bulk-cancel-reason');
            const modalCustomReasonBox = document.getElementById('modal-bulk-custom-reason-box');
            const modalCustomReasonInput = document.getElementById('modal-bulk-custom-reason');
            const modalConfirmBtn = document.getElementById('modal-bulk-cancel-confirm-btn');

            window.closeBulkCancelModal = function () {
                bulkCancelModal?.classList.add('hidden');
            };

            modalReasonSelect?.addEventListener('change', function () {
                if (modalReasonSelect.value === 'Other') {
                    modalCustomReasonBox?.classList.remove('hidden');
                } else {
                    modalCustomReasonBox?.classList.add('hidden');
                }
            });

            modalConfirmBtn?.addEventListener('click', function () {
                let reason = modalReasonSelect.value;
                if (!reason) {
                    alert('Please select a cancellation reason.');
                    return;
                }
                if (reason === 'Other' && modalCustomReasonInput && modalCustomReasonInput.value.trim() !== '') {
                    reason = modalCustomReasonInput.value.trim();
                }
                if (bulkCancelReasonInput) {
                    bulkCancelReasonInput.value = reason;
                }
                bulkCancelModal?.classList.add('hidden');
                bulkForm?.submit();
            });

            bulkForm?.addEventListener('submit', function (e) {
                const submitter = e.submitter;
                if (submitter && (submitter.value === 'print' || submitter.value === 'print_invoice' || submitter.value === 'print_sticker')) {
                    const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
                    if (checkedCount === 0) {
                        alert('Please select at least one order to print.');
                        e.preventDefault();
                        return;
                    }
                }
                if (submitter && submitter.name === 'action' && submitter.value === 'status') {
                    const isOrdersSection = '{{ $currentSection ?? "orders" }}' === 'orders';
                    if (isOrdersSection && bulkStatusSelect && bulkStatusSelect.value === 'cancelled' && (!bulkCancelReasonInput || !bulkCancelReasonInput.value)) {
                        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
                        if (checkedCount === 0) {
                            alert('Please select at least one order.');
                            e.preventDefault();
                            return;
                        }
                        e.preventDefault();
                        bulkCancelModal?.classList.remove('hidden');
                    }
                }
            });
        </script>
    @endif

    {{-- Order Import Modal --}}
    <div id="order-import-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl ring-1 ring-ink/10">
            <div class="mb-4 flex items-center justify-between border-b border-ink/10 pb-3">
                <div class="flex items-center gap-2">
                    <div class="rounded-lg bg-sage/10 p-2 text-sage">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-ink">Import Orders</h3>
                        <p class="text-xs text-muted">Batch import customer orders via CSV</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('order-import-modal').classList.add('hidden')" class="rounded-md p-1 text-muted hover:bg-mist hover:text-ink">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.orders.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-ink mb-1">Select CSV File</label>
                    <input type="file" name="csv_file" accept=".csv,text/csv" required class="block w-full text-xs text-ink file:mr-3 file:rounded-md file:border-0 file:bg-sage/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-sage hover:file:bg-sage/20">
                    <p class="mt-1 text-[11px] text-muted">Required columns: Customer Name, Customer Phone, Customer Address. Optional: Product SKU / Name, Quantity, Unit Price, Shipping Charge, Discount, Payment Method, Notes.</p>
                </div>

                <div class="rounded-lg bg-mist/60 p-3 text-xs flex items-center justify-between">
                    <div>
                        <span class="font-medium text-ink">Need the file format?</span>
                        <p class="text-[11px] text-muted">Download sample orders template</p>
                    </div>
                    <a href="{{ route('admin.orders.sample-template') }}" class="admin-btn-secondary !px-2.5 !py-1 !text-xs font-medium inline-flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Sample CSV
                    </a>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2 border-t border-ink/10 pt-3">
                    <button type="button" onclick="document.getElementById('order-import-modal').classList.add('hidden')" class="admin-btn-secondary">Cancel</button>
                    <button type="submit" class="admin-btn-primary">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
@endsection
