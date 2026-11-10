@extends('layouts.admin')

@php
    use App\Support\Money;
    use Illuminate\Support\Str;

    $fraudReport = is_array($order->fraud_report) ? $order->fraud_report : [];
    $fraudReports = is_array($fraudReport['reports'] ?? null) ? $fraudReport['reports'] : [];
    $ourRecord = $ourRecord ?? \App\Support\CourierRatioCards::ourRecordForPhone($order->customer_phone);
    $statusBadge = match ($order->status) {
        'pending', 'unpaid' => 'admin-badge-warning',
        'confirmed', 'incomplete_to_confirmed' => 'admin-badge-success',
        'processing', 'on_the_way', 'in_courier' => 'admin-badge-info',
        'on_hold' => 'admin-badge-warning',
        'no_response' => 'admin-badge-neutral',
        'delivered', 'completed' => 'admin-badge-success',
        'refunded', 'returned' => 'admin-badge-warning',
        'cancelled', 'courier_returned' => 'admin-badge-danger',
        default => 'admin-badge-neutral',
    };
    $hasFreeDelivery = $order->shipping_charge <= 0 || $order->items->contains(function ($item) {
        return (bool) ($item->product?->free_delivery || $item->variant?->isFreeDelivery());
    });
    $initialItems = $order->items->map(function ($item) {
        $imagePath = $item->product?->primaryImagePath();

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'sku' => $item->product?->sku,
            'slug' => $item->product?->slug,
            'color' => $item->color,
            'size' => $item->size,
            'variation_name' => $item->variation_name,
            'variant_id' => $item->variant_id,
            'attributes' => $item->attributes,
            'free_delivery' => (bool) ($item->product?->free_delivery || $item->variant?->isFreeDelivery()),
            'qty' => (int) $item->qty,
            'price' => (float) $item->price,
            'discount' => (float) $item->discount,
            'stock' => $item->product?->stock,
            'image_url' => $imagePath ? asset('storage/'.$imagePath) : null,
        ];
    })->values();
    $deliveryLocationLabel = collect([$order->delivery_upazila, $order->delivery_district, $order->delivery_division])->filter()->implode(', ');
    $telemetry = app(\App\Services\ClientTelemetryService::class);
    $ipGeo = $telemetry->resolveIpLocation($order->ip_address);
    $deviceInfo = $telemetry->describeUserAgent($order->user_agent);
    $visitor = is_array(data_get($order->attribution_json, 'visitor')) ? data_get($order->attribution_json, 'visitor') : [];
    $ipIsCloudflare = (bool) ($ipGeo['is_cloudflare'] ?? false) || \App\Support\ClientIp::isCloudflare($order->ip_address);
    $visitorCity = trim((string) ($visitor['city'] ?? ''));
    $visitorRegion = trim((string) ($visitor['region'] ?? ''));
    $visitorCountry = trim((string) ($visitor['country'] ?? ''));
    $deviceLabel = trim(implode(' · ', array_filter([
        $deviceInfo['device_type'] ?? null,
        trim(($deviceInfo['os'] ?? '').' '.($deviceInfo['os_version'] ?? '')),
        trim(($deviceInfo['browser'] ?? '').' '.($deviceInfo['browser_version'] ?? '')),
    ])));
@endphp

@section('title', 'Web Order Details')
@section('heading')
    <span class="inline-flex items-center gap-2">
        <a href="{{ route('admin.orders.index', ['section' => ($orderSection ?? ($order->isCourier() ? 'courier' : 'orders'))]) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-ink/10 bg-white text-muted shadow-sm hover:bg-mist hover:text-ink" title="Back to list">
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.56l3.22 3.22a.75.75 0 1 1-1.06 1.06l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 0 1 1.06 1.06L5.56 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
        </a>
        <span>
            Web Order Details
            <span class="ml-1.5 font-mono text-sm font-normal text-muted">#{{ $order->invoice_no }}</span>
        </span>
    </span>
@endsection
@section('subheading', 'Review customer, products, courier ratio, and traffic source.')

@section('actions')
    <a href="{{ route('admin.orders.print', $order) }}" target="_blank" class="admin-btn bg-indigo-50 text-indigo-700 hover:bg-indigo-100" title="Print (Invoice / Sticker)">
        <svg class="h-3.5 w-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print
    </a>
    @if (($order->payment_status ?? 'pending') === 'paid')
        <span class="admin-badge-pill bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold">PAID ({{ strtoupper($order->payment_method ?? 'ONLINE') }})</span>
    @else
        <span class="admin-badge-pill bg-amber-50 text-amber-800 border border-amber-200 font-semibold">UNPAID ({{ strtoupper($order->payment_method ?? 'COD') }})</span>
    @endif
    <span class="admin-badge-pill {{ $statusBadge }}">{{ $order->statusLabel() }}</span>
    @if ($order->isCourierBooked())
        <span class="admin-badge-pill bg-purple-50 text-purple-700 border border-purple-200 font-semibold inline-flex items-center gap-1">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            COURIER BOOKED
        </span>
    @endif
    <span class="admin-badge-pill admin-badge-sage">{{ strtoupper($order->source ?: 'WEB') }}</span>
    <span class="hidden text-xs text-muted sm:inline">{{ $order->created_at?->format('d M Y, g:i A') }}</span>
@endsection

@section('content')
    <div class="order-detail space-y-1.5">
        {{-- Top row: left Courier ratio (70%) | right Actions (30%) --}}
        <div class="order-detail-top">
            <div class="od-panel order-detail-top__ratio">
                <div class="od-panel-h">
                    <h2 class="admin-section-title !mb-0">Courier ratio report</h2>
                    <form method="POST" action="{{ route('admin.orders.fraud-check', $order) }}">
                        @csrf
                        <button type="submit" class="admin-btn bg-teal-600 text-white hover:bg-teal-700">Check Ratio</button>
                    </form>
                </div>
                <div class="od-panel-b !py-1.5">
                    @include('admin.partials.courier-ratio-cards', [
                        'fraudReport' => $fraudReport,
                        'ourRecord' => $ourRecord,
                        'scrollKey' => 'order-'.$order->id,
                    ])
                    @if (count($fraudReports) > 0)
                        <p class="mt-1 truncate text-[10px] text-muted">
                            @foreach ($fraudReports as $report)
                                @continue(! is_array($report))
                                {{ $report['name'] ?? 'Report' }}@if (! empty($report['details'])) — {{ $report['details'] }}@endif{{ ! $loop->last ? ' · ' : '' }}
                            @endforeach
                        </p>
                    @endif
                </div>
            </div>

            <div class="od-panel order-detail-top__actions">
                <div class="od-panel-h">
                    <h2 class="admin-section-title !mb-0">Actions</h2>
                </div>
                <div class="od-panel-b flex h-full flex-col gap-1">
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="admin-label" for="status">Status</label>
                            <span class="text-[10px] font-semibold uppercase tracking-wider {{ ($orderSection ?? ($order->isCourier() ? 'courier' : 'orders')) === 'courier' ? 'text-purple-700' : 'text-blue-700' }}">
                                {{ ($orderSection ?? ($order->isCourier() ? 'courier' : 'orders')) === 'courier' ? 'Courier Lifecycle' : 'All Orders Lifecycle' }}
                            </span>
                        </div>
                        <div class="flex gap-0.5">
                            <select id="status" name="status" form="order-status-form" class="field" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->slug }}" @selected(old('status', $order->status) === $status->slug)>{{ $status->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" form="order-status-form" class="admin-btn shrink-0 bg-emerald-600 text-white hover:bg-emerald-700 px-3 font-medium">Update</button>
                        </div>
                        <div id="detail-cancel-reason-box" class="mt-1 {{ old('status', $order->status) === 'cancelled' ? '' : 'hidden' }}">
                            <label class="admin-label !text-red-600" for="detail_cancel_reason">Cancellation Reason <span class="text-red-500">*</span></label>
                            <select id="detail_cancel_reason" class="field w-full text-xs">
                                <option value="">Select reason…</option>
                                @foreach (\App\Models\Order::CANCEL_REASONS as $reasonKey => $reasonLabel)
                                    <option value="{{ $reasonKey }}" @selected(old('cancel_reason', $order->cancel_reason) === $reasonKey)>{{ $reasonLabel }}</option>
                                @endforeach
                            </select>
                            <input type="text" id="detail_custom_cancel_reason" class="field mt-1 w-full text-xs hidden" placeholder="Specify custom reason…">
                        </div>
                    </div>
                    <div class="min-h-0 flex-1">
                        <label class="admin-label" for="admin_notes">Admin note</label>
                        <textarea id="admin_notes" name="admin_notes" form="order-status-form" rows="3" class="field h-[calc(100%-1rem)] min-h-[3.5rem]">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                    </div>
                    <div class="mt-2 flex items-center justify-between border-t border-ink/10 pt-2">
                        <a href="{{ route('admin.refunds.create', ['order_id' => $order->id]) }}" class="admin-btn border border-ink/15 bg-white text-ink hover:bg-mist text-xs">
                            Refund
                        </a>
                        @if ($order->isDeletable(auth('admin')->user()))
                            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Are you sure you want to delete this order? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-xs">
                                    Delete Order
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 px-2 py-1 text-[11px] text-red-800">
                {{ $errors->first() }}@if ($errors->count() > 1) (+{{ $errors->count() - 1 }})@endif
            </div>
        @endif

        @if ($order->isCourierBooked())
            <div class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-900 flex items-center gap-2 shadow-xs">
                <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>
                    <strong>Order Locked:</strong> This order has already been booked with courier (Tracking #{{ $order->tracking_number }}). Editing customer details and line items is disabled.
                </span>
            </div>
        @endif

        {{-- Main: left 70% (Customer + Products) | right 30% (Summary + Traffic + Device) --}}
        <div class="order-detail-main">
            <div class="order-detail-main__left">
                <form id="order-edit-form" method="POST" action="{{ route('admin.orders.update', $order) }}" class="space-y-1.5">
                    @csrf
                    @method('PUT')

                    <div class="od-panel">
                        <div class="od-panel-h">
                            <div class="flex items-center gap-2">
                                <h2 class="admin-section-title !mb-0">Customer &amp; shipping</h2>
                                @if ($order->isCourierBooked())
                                    <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 border border-amber-200">Locked</span>
                                @endif
                            </div>
                            @unless ($order->isCourierBooked())
                                <button type="submit" name="action" value="save" class="admin-btn bg-sage text-white hover:bg-sage/90">Save</button>
                            @endunless
                        </div>
                        <div class="od-panel-b grid grid-cols-2 gap-1 sm:grid-cols-4">
                            <div class="col-span-2 sm:col-span-2">
                                <label class="admin-label" for="customer_phone">Mobile</label>
                                <input id="customer_phone" type="text" name="customer_phone" value="{{ old('customer_phone', $order->customer_phone) }}" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-600' : '' }}" {{ $order->isCourierBooked() ? 'readonly' : 'required' }}>
                            </div>
                            <div class="col-span-2 sm:col-span-2">
                                <label class="admin-label" for="customer_name">Name</label>
                                <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name', $order->customer_name) }}" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-600' : '' }}" {{ $order->isCourierBooked() ? 'readonly' : 'required' }}>
                            </div>
                            <div class="col-span-2 sm:col-span-4">
                                <label class="admin-label" for="customer_address">Address</label>
                                <textarea id="customer_address" name="customer_address" rows="2" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-600' : '' }}" {{ $order->isCourierBooked() ? 'readonly' : 'required' }}>{{ old('customer_address', $order->customer_address) }}</textarea>
                            </div>
                            <div class="col-span-2 sm:col-span-4">
                                <label class="admin-label" for="notes">Note <span id="note-count" class="text-muted">{{ strlen((string) old('notes', $order->notes)) }}</span>/350</label>
                                <textarea id="notes" name="notes" rows="1" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-600' : '' }}" maxlength="350" {{ $order->isCourierBooked() ? 'readonly' : '' }}>{{ old('notes', $order->notes) }}</textarea>
                            </div>
                            <div>
                                <label class="admin-label" for="delivery_division">Division</label>
                                <select id="delivery_division" name="delivery_division" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-500' : '' }}" {{ $order->isCourierBooked() ? 'disabled' : '' }}>
                                    <option value="">Select Division</option>
                                    @foreach (\App\Support\BangladeshLocations::divisions() as $divName)
                                        <option value="{{ $divName }}" @selected(old('delivery_division', $order->delivery_division) === $divName)>{{ $divName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="admin-label" for="delivery_district">District</label>
                                <select id="delivery_district" name="delivery_district" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-500' : '' }}" {{ $order->isCourierBooked() ? 'disabled' : '' }}>
                                    <option value="">Select District</option>
                                </select>
                            </div>
                            <div>
                                <label class="admin-label" for="delivery_upazila">Upazila</label>
                                <select id="delivery_upazila" name="delivery_upazila" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-500' : '' }}" {{ $order->isCourierBooked() ? 'disabled' : '' }}>
                                    <option value="">Select Upazila</option>
                                </select>
                            </div>
                            <div>
                                <label class="admin-label" for="payment_method">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-500' : '' }}" {{ $order->isCourierBooked() ? 'disabled' : 'required' }}>
                                    @foreach (['cod' => 'COD', 'sslcommerz' => 'SSLCommerz', 'manual' => 'Manual'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('payment_method', $order->payment_method) === $value)>{{ $label }}</option>
                                    @endforeach
                                    @if (! in_array(old('payment_method', $order->payment_method), ['cod', 'sslcommerz', 'manual'], true))
                                        <option value="{{ old('payment_method', $order->payment_method) }}" selected>{{ old('payment_method', $order->payment_method) }}</option>
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label class="admin-label" for="payment_status">Payment Status</label>
                                <select id="payment_status" name="payment_status" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-500' : '' }}" {{ $order->isCourierBooked() ? 'disabled' : 'required' }}>
                                    <option value="pending" @selected(old('payment_status', $order->payment_status ?? 'pending') === 'pending')>Unpaid (Pending)</option>
                                    <option value="paid" @selected(old('payment_status', $order->payment_status ?? 'pending') === 'paid')>Paid</option>
                                    <option value="failed" @selected(old('payment_status', $order->payment_status ?? 'pending') === 'failed')>Failed</option>
                                    <option value="refunded" @selected(old('payment_status', $order->payment_status ?? 'pending') === 'refunded')>Refunded</option>
                                </select>
                            </div>
                            @if ($order->tracking_number)
                                <div class="col-span-2 space-y-1 border-t border-ink/5 pt-1 sm:col-span-4">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <a href="{{ route('admin.orders.print', $order) }}" target="_blank" class="admin-btn bg-mist" title="Print (Invoice / Sticker)">
                                            <svg class="h-3.5 w-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            Print
                                        </a>
                                        <span class="text-[10px] text-muted">
                                            Consignment:
                                            @if ($order->tracking_url)
                                                <a href="{{ $order->tracking_url }}" target="_blank" class="font-medium text-sage hover:underline">{{ $order->tracking_number }}</a>
                                            @else
                                                <span class="font-medium text-ink">{{ $order->tracking_number }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    @if ($order->courier_synced_at)
                                        <div class="text-[10px] text-muted">
                                            Last courier sync {{ $order->courier_synced_at->diffForHumans() }}.
                                            Status updates via webhooks / <code class="text-[10px]">courier:check-status</code> cron.
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="od-panel od-products-panel">
                        <div class="od-panel-b space-y-3">
                            @unless ($order->isCourierBooked())
                                <div>
                                    <h2 class="text-[0.95rem] font-semibold tracking-tight text-ink">Add products</h2>
                                    <p class="mt-0.5 text-[11px] text-muted">Search by product name and SKU</p>
                                    <div class="relative mt-2">
                                        <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-muted" aria-hidden="true">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                                        </span>
                                        <input id="product-search" type="search" autocomplete="off" placeholder="Search and add products" class="field !pl-8">
                                        <div id="product-search-results" class="absolute z-20 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg border border-ink/10 bg-white shadow-lg"></div>
                                    </div>
                                </div>
                            @endunless

                            <div>
                                <div class="mb-2 flex items-center gap-2">
                                    <h2 class="text-[0.95rem] font-semibold tracking-tight text-ink">Ordered Products</h2>
                                    <span id="items-count" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-violet-100 px-1.5 text-[11px] font-semibold text-violet-700">{{ $order->items->count() }}</span>
                                </div>
                                <div id="order-items-list" class="space-y-2"></div>
                                <div id="items-hidden-inputs"></div>
                            </div>

                            <div>
                                <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-sky-600">Order total</div>
                                <div class="od-order-total">
                                    <div>
                                        <label class="admin-label" for="discount">Discount</label>
                                        <input id="discount" type="number" name="discount" min="0" step="0.01" value="{{ old('discount', $order->discount) }}" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-600' : '' }}" {{ $order->isCourierBooked() ? 'readonly' : 'required' }}>
                                    </div>
                                    <div>
                                        <label class="admin-label" for="advance_amount">Advance</label>
                                        <input id="advance_amount" type="number" min="0" step="0.01" value="0" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-600' : '' }}" placeholder="0" {{ $order->isCourierBooked() ? 'readonly' : '' }}>
                                    </div>
                                    <div>
                                        <label class="admin-label" for="summary-subtotal-input">Sub Total</label>
                                        <input id="summary-subtotal-input" type="text" class="field bg-mist" value="{{ number_format((float) $order->subtotal, 2, '.', '') }}" readonly>
                                    </div>
                                    <div class="od-order-total__delivery">
                                        <label class="admin-label" for="shipping_charge_select">Delivery Area</label>
                                        <div class="od-delivery-box">
                                            <select id="shipping_charge_select" class="od-delivery-box__method {{ $order->isCourierBooked() ? 'cursor-not-allowed opacity-75' : '' }}" aria-label="Delivery area" {{ $order->isCourierBooked() ? 'disabled' : '' }}>
                                                <option value="" data-amount="">Custom</option>
                                                <option value="free" data-amount="0.00" @selected((float) old('shipping_charge', $order->shipping_charge) <= 0)>Free Delivery (৳0.00)</option>
                                                @foreach ($shippingCharges as $charge)
                                                    <option
                                                        value="{{ $charge->id }}"
                                                        data-amount="{{ number_format((float) $charge->amount, 2, '.', '') }}"
                                                        @selected((float) old('shipping_charge', $order->shipping_charge) === (float) $charge->amount)
                                                    >{{ $charge->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="od-delivery-box__amount">
                                                <span class="od-delivery-box__currency">৳</span>
                                                <input
                                                    id="shipping_charge"
                                                    type="number"
                                                    name="shipping_charge"
                                                    min="0"
                                                    step="0.01"
                                                    value="{{ old('shipping_charge', $order->shipping_charge) }}"
                                                    aria-label="Delivery charge amount"
                                                    {{ $order->isCourierBooked() ? 'readonly' : 'required' }}
                                                    class="{{ $order->isCourierBooked() ? 'cursor-not-allowed' : '' }}"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                    <div class="od-order-total__method">
                                        <label class="admin-label" for="delivery_method">Delivery Method</label>
                                        @if (! empty($courierProviders))
                                            <select id="delivery_method" name="courier_api_id" class="field {{ $order->isCourierBooked() ? 'bg-slate-50 cursor-not-allowed text-slate-500' : '' }}" aria-label="Delivery method / courier" {{ $order->isCourierBooked() ? 'disabled' : '' }}>
                                                <option value="">Select courier</option>
                                                @foreach ($courierProviders as $courier)
                                                    <option value="{{ $courier['id'] }}" @selected((int) old('courier_api_id', $order->courier_api_id) === (int) $courier['id'])>
                                                        {{ $courier['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" class="field bg-mist text-muted" value="No courier configured" readonly>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="admin-label !text-red-600" for="summary-total-input">Grand Total</label>
                                        <input id="summary-total-input" type="text" class="field border-red-300 bg-red-50 font-semibold text-red-700" value="{{ number_format((float) $order->total, 2, '.', '') }}" readonly>
                                    </div>
                                </div>
                            </div>

                            @unless ($order->isCourierBooked())
                                <div class="mt-3">
                                    <button type="submit" name="action" value="{{ $order->status === 'confirmed' ? 'save' : 'confirm' }}" id="save-order-btn" class="od-save-btn w-full">
                                        {{ $order->status === 'confirmed' ? 'Save Order' : 'Confirm Order' }} ({{ number_format((float) $order->total, 2) }}৳)
                                    </button>
                                </div>
                            @else
                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50/70 p-2.5 text-center text-xs font-semibold text-amber-800 flex items-center justify-center gap-1.5">
                                    <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Order locked from editing (Courier Booked)
                                </div>
                            @endunless
                        </div>
                    </div>
                </form>
            </div>

            <aside class="order-detail-main__right space-y-1.5">
                    <div class="od-panel">
                        <div class="od-panel-h"><h2 class="admin-section-title">Order summary</h2></div>
                        <div class="od-panel-b">
                            @if ($order->status === 'cancelled' && $order->cancel_reason)
                                <div class="mb-2 rounded border border-red-200 bg-red-50 p-2 text-xs text-red-800">
                                    <div class="font-semibold text-red-700">Cancellation Reason:</div>
                                    <div class="mt-0.5">{{ $order->cancel_reason }}</div>
                                </div>
                            @endif
                            <div class="order-detail-meta">
                                <dt>Invoice</dt>
                                <dd>
                                    @if ($order->tracking_number)
                                        #{{ $order->tracking_number }}
                                    @else
                                        <span class="text-muted">After courier booking</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="order-detail-meta"><dt>Order ref</dt><dd>#{{ $order->invoice_no }}</dd></div>
                            <div class="order-detail-meta"><dt>Order ID</dt><dd>{{ $order->id }}</dd></div>
                            <div class="order-detail-meta"><dt>Status</dt><dd><span class="admin-badge-pill {{ $statusBadge }}">{{ $order->statusLabel() }}</span></dd></div>
                            <div class="order-detail-meta"><dt>Payment</dt><dd>{{ strtoupper($order->payment_method) }} · {{ str($order->payment_status ?? 'pending')->title() }}</dd></div>
                            <div class="order-detail-meta"><dt>Date</dt><dd>{{ $order->created_at?->format('d M Y, g:i A') }}</dd></div>
                            <div class="order-detail-meta"><dt>Subtotal</dt><dd id="sidebar-subtotal">{{ Money::format($order->subtotal) }}</dd></div>
                            <div class="order-detail-meta"><dt>Delivery</dt><dd id="sidebar-shipping">{{ Money::format($order->shipping_charge) }}</dd></div>
                            <div class="order-detail-meta"><dt>Discount</dt><dd id="sidebar-discount">{{ Money::format($order->discount) }}</dd></div>
                            <div class="order-detail-meta text-[0.8rem] font-semibold"><dt>Total</dt><dd id="sidebar-total" class="text-sage">{{ Money::format($order->total) }}</dd></div>
                            <div class="mt-2 space-y-1 border-t border-ink/8 pt-2" id="sidebar-items">
                                @forelse ($order->items as $item)
                                    <div class="flex justify-between gap-2 text-[11px]">
                                        <span class="truncate font-medium">{{ $item->product_name }}</span>
                                        <span class="shrink-0 text-muted">{{ $item->qty }}× {{ Money::format($item->price) }}</span>
                                    </div>
                                @empty
                                    <p class="text-[11px] text-muted">No items</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    @if (!empty($order->custom_fields) && is_array($order->custom_fields))
                        <div class="od-panel">
                            <div class="od-panel-h"><h2 class="admin-section-title">Custom Checkout Fields</h2></div>
                            <div class="od-panel-b space-y-1">
                                @foreach ($order->custom_fields as $cfKey => $cfVal)
                                    <div class="order-detail-meta">
                                        <dt class="font-medium text-slate-700">{{ str($cfKey)->replace('_', ' ')->title() }}</dt>
                                        <dd class="font-semibold text-ink">{{ is_array($cfVal) ? json_encode($cfVal) : $cfVal }}</dd>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="od-panel">
                        <div class="od-panel-h"><h2 class="admin-section-title">Traffic / Source</h2></div>
                        <div class="od-panel-b">
                            <div class="order-detail-meta">
                                <dt>Platform</dt>
                                <dd>@include('admin.partials.platform-badge', ['platform' => $order->source_platform ?: 'direct'])</dd>
                            </div>
                            <div class="order-detail-meta"><dt>Channel source</dt><dd>{{ $order->source ?: 'web' }}</dd></div>
                            @if ($order->user)
                                <div class="order-detail-meta">
                                    <dt>Account</dt>
                                    <dd>
                                        <a href="{{ route('admin.customers.show', $order->user) }}">{{ Str::limit($order->user->email, 28) }}</a>
                                    </dd>
                                </div>
                            @endif
                            <div class="order-detail-meta"><dt>UTM source</dt><dd>{{ $order->utm_source ?: '—' }}</dd></div>
                            <div class="order-detail-meta"><dt>UTM medium</dt><dd>{{ $order->utm_medium ?: '—' }}</dd></div>
                            <div class="order-detail-meta"><dt>Campaign</dt><dd>{{ $order->utm_campaign ?: '—' }}</dd></div>
                            <div class="order-detail-meta"><dt>Content</dt><dd>{{ $order->utm_content ?: '—' }}</dd></div>
                            <div class="order-detail-meta"><dt>Term</dt><dd>{{ $order->utm_term ?: '—' }}</dd></div>
                            <div class="order-detail-meta">
                                <dt>Landing</dt>
                                <dd>
                                    @if ($order->landing_url)
                                        <a href="{{ $order->landing_url }}" target="_blank" rel="noopener" title="{{ $order->landing_url }}">{{ Str::limit($order->landing_url, 34) }}</a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="order-detail-meta">
                                <dt>Referrer</dt>
                                <dd title="{{ $order->referrer }}">{{ $order->referrer ? Str::limit($order->referrer, 34) : '—' }}</dd>
                            </div>
                            <div class="order-detail-meta"><dt>fbclid</dt><dd class="font-mono text-[10px]">{{ $order->fbclid ? Str::limit($order->fbclid, 18) : '—' }}</dd></div>
                            <div class="order-detail-meta"><dt>gclid</dt><dd class="font-mono text-[10px]">{{ $order->gclid ? Str::limit($order->gclid, 18) : '—' }}</dd></div>
                            <div class="order-detail-meta"><dt>ttclid</dt><dd class="font-mono text-[10px]">{{ $order->ttclid ? Str::limit($order->ttclid, 18) : '—' }}</dd></div>
                        </div>
                    </div>

                    <div class="od-panel">
                        <div class="od-panel-h flex items-center justify-between">
                            <h2 class="admin-section-title">Activity &amp; Edit History</h2>
                            <span class="rounded bg-mist px-1.5 py-0.5 text-[10px] font-semibold text-ink">{{ $order->activityLogs->count() }}</span>
                        </div>
                        <div class="od-panel-b p-3 space-y-2.5 text-[11px] leading-snug max-h-80 overflow-y-auto">
                            @php
                                $fieldPrettyNames = [
                                    'customer_name' => 'Customer Name',
                                    'customer_phone' => 'Customer Phone',
                                    'customer_address' => 'Customer Address',
                                    'delivery_division' => 'Division',
                                    'delivery_district' => 'District',
                                    'delivery_upazila' => 'Upazila',
                                    'shipping_charge' => 'Delivery Charge',
                                    'discount' => 'Discount Amount',
                                    'total' => 'Total Amount',
                                    'subtotal' => 'Subtotal',
                                    'payment_method' => 'Payment Method',
                                    'payment_status' => 'Payment Status',
                                    'status' => 'Status',
                                    'admin_notes' => 'Admin Note',
                                    'notes' => 'Customer Note',
                                    'tracking_number' => 'Tracking Number',
                                    'courier' => 'Courier Service',
                                    'courier_api_id' => 'Courier Service',
                                    'courier_service' => 'Courier Service',
                                ];
                                $courierNameMap = \App\Models\CourierApi::query()->pluck('name', 'id')->all();
                            @endphp
                            @forelse ($order->activityLogs as $log)
                                <div class="rounded-lg border border-ink/8 bg-mist/30 p-2.5 space-y-1.5">
                                    <div class="flex items-center justify-between gap-1 text-[10px]">
                                        <span class="font-bold text-ink inline-flex items-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                                            {{ $log->causer_name ?: 'System' }}
                                        </span>
                                        <span class="text-muted text-[10px]" title="{{ $log->created_at?->format('d M Y, h:i:s A') }}">
                                            {{ $log->created_at?->diffForHumans() }} ({{ $log->created_at?->format('h:i A') }})
                                        </span>
                                    </div>

                                    <div class="font-semibold text-ink text-[11px]">
                                        {{ $log->description ?: ucfirst(str_replace('_', ' ', $log->action)) }}
                                    </div>

                                    @if (! empty($log->changes))
                                        <div class="mt-1.5 space-y-1 pt-1.5 border-t border-ink/5 text-[11px]">
                                            @foreach ($log->changes as $fld => $diff)
                                                @if (is_array($diff) && (isset($diff['old']) || isset($diff['new'])))
                                                    @php
                                                        $oldValDisplay = $diff['old'];
                                                        $newValDisplay = $diff['new'];

                                                        // If it was stored as an integer ID for courier_api_id
                                                        if (in_array($fld, ['courier_api_id', 'courier_service', 'courier'], true)) {
                                                            if (is_numeric($oldValDisplay) && isset($courierNameMap[$oldValDisplay])) {
                                                                $oldValDisplay = $courierNameMap[$oldValDisplay];
                                                            }
                                                            if (is_numeric($newValDisplay) && isset($courierNameMap[$newValDisplay])) {
                                                                $newValDisplay = $courierNameMap[$newValDisplay];
                                                            }
                                                        }
                                                    @endphp
                                                    <div class="rounded bg-white/70 px-2 py-1 border border-ink/5">
                                                        <span class="font-semibold text-ink">{{ $fieldPrettyNames[$fld] ?? ucfirst(str_replace('_', ' ', $fld)) }}:</span>
                                                        <div class="mt-0.5 flex flex-wrap items-center gap-1 text-[10px]">
                                                            <span class="line-through text-rose-600 bg-rose-50 px-1 py-0.5 rounded">{{ filled($oldValDisplay) ? $oldValDisplay : 'Empty' }}</span>
                                                            <span class="text-muted">→</span>
                                                            <span class="font-medium text-emerald-700 bg-emerald-50 px-1 py-0.5 rounded">{{ filled($newValDisplay) ? $newValDisplay : 'Empty' }}</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-3 text-muted text-xs">
                                    No activity logs recorded yet.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="od-panel">
                        <div class="od-panel-h"><h2 class="admin-section-title">Device &amp; IP Network</h2></div>
                        <div class="od-panel-b space-y-2 text-[11px] leading-snug">
                            <div class="order-detail-meta !border-0 !p-0">
                                <dt>Device</dt>
                                <dd class="font-medium text-ink">{{ filled($order->user_agent) ? ($deviceLabel ?: 'Unknown device') : 'Not captured' }}</dd>
                            </div>

                            <div>
                                <div class="text-[10px] font-medium uppercase tracking-wide text-muted">Location</div>
                                <div class="font-medium text-ink flex items-center gap-1.5 mt-0.5">
                                    <svg class="h-3.5 w-3.5 text-sage shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                    </svg>
                                    <span>
                                        @if (! $ipIsCloudflare && ! ($ipGeo['is_local'] ?? false) && filled($ipGeo['city'] ?? null) && ($ipGeo['city'] ?? '') !== 'Unknown')
                                            <span class="font-bold text-ink">{{ $ipGeo['city'] }}</span>@if (filled($ipGeo['region'] ?? null)), {{ $ipGeo['region'] }}@endif @if (filled($ipGeo['country'] ?? null))({{ $ipGeo['country'] }})@endif
                                        @elseif ($visitorCity !== '')
                                            <span class="font-bold text-ink">{{ $visitorCity }}</span>@if ($visitorRegion !== ''), {{ $visitorRegion }}@endif @if ($visitorCountry !== '')({{ $visitorCountry }})@endif
                                        @elseif ($ipIsCloudflare)
                                            <span class="text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded text-[10px] font-medium">Cloudflare proxy — customer location was not stored</span>
                                        @elseif ($ipGeo['is_local'] ?? false)
                                            <span class="text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded text-[10px] font-medium">Localhost / LAN ({{ $order->ip_address }})</span>
                                        @else
                                            <span class="font-bold text-ink">Unknown</span>
                                        @endif
                                    </span>
                                </div>
                                @if ($ipIsCloudflare && $visitorCity === '' && filled($ipGeo['city'] ?? null) && ($ipGeo['city'] ?? '') !== 'Unknown')
                                    <div class="text-[10px] text-muted mt-0.5 pl-5">Proxy city: {{ $ipGeo['city'] }}@if (filled($ipGeo['country'] ?? null)), {{ $ipGeo['country'] }}@endif</div>
                                @endif
                                @if (! $ipIsCloudflare && ! ($ipGeo['is_local'] ?? false) && filled($ipGeo['isp'] ?? null))
                                    <div class="text-[10px] text-muted mt-0.5 pl-5">ISP: {{ $ipGeo['isp'] }}</div>
                                @endif
                                @if (filled($visitor['timezone'] ?? null))
                                    <div class="text-[10px] text-muted mt-0.5 pl-5">Timezone: {{ $visitor['timezone'] }}</div>
                                @endif
                            </div>

                            <div class="order-detail-meta !border-0 !p-0 pt-1 border-t border-ink/8"><dt>IP address</dt><dd class="font-mono text-[11px]">{{ $order->ip_address ?: '—' }}</dd></div>
                            <div class="order-detail-meta !border-0 !p-0"><dt>Phone</dt><dd class="font-medium text-ink">{{ $order->customer_phone ?: '—' }}</dd></div>
                            <div>
                                <div class="mb-0.5 text-[10px] font-medium uppercase tracking-wide text-muted">User agent</div>
                                <div class="max-h-16 overflow-auto rounded-md border border-ink/8 bg-mist/50 p-1.5 text-[10px] break-all text-muted font-mono">
                                    {{ $order->user_agent ?: '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <form id="order-status-form" method="POST" action="{{ route('admin.orders.status', $order) }}" class="hidden">
            @csrf
            @method('PUT')
            <input type="hidden" name="cancel_reason" id="status-cancel-reason-input" value="{{ old('cancel_reason', $order->cancel_reason) }}">
            <input type="hidden" name="notes" id="status-notes-sync" value="{{ old('notes', $order->notes) }}">
            <input type="hidden" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}">
            <input type="hidden" name="tracking_url" value="{{ old('tracking_url', $order->tracking_url) }}">
        </form>

        {{-- Single Order Cancel Modal --}}
        <div id="single-cancel-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink/50 p-4">
            <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
                <div class="mb-3 flex items-center justify-between border-b border-ink/10 pb-2">
                    <h3 class="font-semibold text-ink">Cancel Order #{{ $order->invoice_no }}</h3>
                    <button type="button" onclick="closeSingleCancelModal()" class="text-lg font-bold leading-none text-muted hover:text-ink">&times;</button>
                </div>
                <p class="mb-3 text-xs text-muted">
                    Please select a reason for cancelling this order.
                </p>
                <div class="space-y-3">
                    <div>
                        <label class="admin-label" for="modal-single-cancel-reason">Cancellation Reason <span class="text-red-500">*</span></label>
                        <select id="modal-single-cancel-reason" class="field w-full text-xs">
                            <option value="">Select reason…</option>
                            @foreach (\App\Models\Order::CANCEL_REASONS as $reasonKey => $reasonLabel)
                                <option value="{{ $reasonKey }}">{{ $reasonLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="modal-single-custom-reason-box" class="hidden">
                        <label class="admin-label" for="modal-single-custom-reason">Custom Reason Note</label>
                        <input type="text" id="modal-single-custom-reason" class="field w-full text-xs" placeholder="Specify custom reason…">
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="closeSingleCancelModal()" class="admin-btn bg-mist text-ink hover:bg-ink/10">Back</button>
                    <button type="button" id="modal-single-cancel-confirm-btn" class="admin-btn bg-red-600 text-white hover:bg-red-700">Confirm Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const searchUrl = @json(route('admin.orders.products.search'));
            let items = @json($initialItems);
            const isLocked = @json($order->isCourierBooked());

            const list = document.getElementById('order-items-list');
            const hidden = document.getElementById('items-hidden-inputs');
            const searchInput = document.getElementById('product-search');
            const resultsBox = document.getElementById('product-search-results');
            const shippingInput = document.getElementById('shipping_charge');
            const shippingSelect = document.getElementById('shipping_charge_select');
            const discountInput = document.getElementById('discount');
            const advanceInput = document.getElementById('advance_amount');
            const subtotalInput = document.getElementById('summary-subtotal-input');
            const totalInput = document.getElementById('summary-total-input');
            const saveBtn = document.getElementById('save-order-btn');
            const itemsCount = document.getElementById('items-count');
            const sidebarSubtotal = document.getElementById('sidebar-subtotal');
            const sidebarShipping = document.getElementById('sidebar-shipping');
            const sidebarDiscount = document.getElementById('sidebar-discount');
            const sidebarTotal = document.getElementById('sidebar-total');
            const sidebarItems = document.getElementById('sidebar-items');
            const noteField = document.getElementById('notes');
            const noteCount = document.getElementById('note-count');
            const statusNotesSync = document.getElementById('status-notes-sync');

            const money = (n) => '৳' + Number(n).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            const plainMoney = (n) => Number(n).toFixed(2);
            const lineTotal = (item) => Math.max(0, (Number(item.price) * Number(item.qty)) - Number(item.discount || 0));
            const itemTitle = (item) => item.sku || item.slug || item.product_name;
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            const syncHidden = () => {
                hidden.innerHTML = '';
                items.forEach((item, index) => {
                    const fields = {
                        product_id: item.product_id || '',
                        product_name: item.product_name,
                        variant_id: item.variant_id || '',
                        variation_name: item.variation_name || '',
                        color: item.color || '',
                        size: item.size || '',
                        qty: item.qty,
                        price: Number(item.price).toFixed(2),
                        discount: Number(item.discount || 0).toFixed(2),
                    };
                    if (item.id) fields.id = item.id;
                    Object.entries(fields).forEach(([key, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `items[${index}][${key}]`;
                        input.value = value;
                        hidden.appendChild(input);
                    });
                });
            };

            const recalculate = () => {
                const subtotal = items.reduce((sum, item) => sum + (Number(item.price) * Number(item.qty)), 0);
                const shipping = Number(shippingInput?.value || 0);
                const discount = Number(discountInput?.value || 0);
                const advance = Number(advanceInput?.value || 0);
                const orderTotal = Math.max(0, subtotal - discount + shipping);
                const dueTotal = Math.max(0, orderTotal - advance);

                if (subtotalInput) subtotalInput.value = plainMoney(subtotal);
                if (totalInput) totalInput.value = plainMoney(dueTotal);
                if (saveBtn) {
                    const isConfirmed = @json($order->status === 'confirmed');
                    saveBtn.textContent = isConfirmed
                        ? `Save Order (${orderTotal.toFixed(2)}৳)`
                        : `Confirm Order (${orderTotal.toFixed(2)}৳)`;
                }
                if (itemsCount) itemsCount.textContent = String(items.length);
                if (sidebarSubtotal) sidebarSubtotal.textContent = money(subtotal);
                if (sidebarShipping) sidebarShipping.textContent = money(shipping);
                if (sidebarDiscount) sidebarDiscount.textContent = money(discount);
                if (sidebarTotal) sidebarTotal.textContent = money(orderTotal);

                if (sidebarItems) {
                    sidebarItems.innerHTML = items.length
                        ? items.map((item) => `<div class="flex justify-between gap-2 text-[11px]"><span class="truncate font-medium">${escapeHtml(item.product_name)}</span><span class="shrink-0 text-muted">${item.qty}× ${money(item.price)}</span></div>`).join('')
                        : '<p class="text-[11px] text-muted">No items</p>';
                }

                syncHidden();
            };

            const render = () => {
                if (! items.length) {
                    list.innerHTML = '<div class="rounded-lg border border-dashed border-ink/15 px-3 py-4 text-center text-[12px] text-muted">No products added yet</div>';
                    recalculate();
                    return;
                }

                list.innerHTML = items.map((item, index) => `
                    <div class="order-detail-item" data-index="${index}">
                        <div class="order-detail-item__top">
                            <div class="order-detail-item__media">
                                ${item.image_url ? `<img src="${escapeHtml(item.image_url)}" alt="" class="h-full w-full object-cover">` : ''}
                            </div>
                            <div class="order-detail-item__info min-w-0">
                                <div class="truncate text-[13px] font-semibold leading-tight text-ink">${escapeHtml(itemTitle(item))}</div>
                                <div class="mt-0.5 truncate text-[11px] text-muted">${escapeHtml(item.product_name)}${item.variation_name ? ` · <span class="font-medium text-ink">${escapeHtml(item.variation_name)}</span>` : ''}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px]">
                                    <span class="font-medium text-ink">${money(item.price)}</span>
                                    <span class="text-muted">Stock: ${item.stock ?? '—'}</span>
                                </div>
                            </div>
                            ${isLocked ? '' : `
                                <button type="button" data-remove class="order-detail-item__remove" title="Remove">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75V4.5H3.75a.75.75 0 0 0 0 1.5h.55l.7 10.15A2.25 2.25 0 0 0 7.24 18h5.52a2.25 2.25 0 0 0 2.24-2.05l.7-10.15h.55a.75.75 0 0 0 0-1.5H14v-.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM7.5 4.5v-.75c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25V4.5h-5ZM8.5 8a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-1.5 0v-6.5A.75.75 0 0 1 8.5 8Zm3 0a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-1.5 0v-6.5A.75.75 0 0 1 11.5 8Z" clip-rule="evenodd"/></svg>
                                </button>
                            `}
                        </div>
                        <div class="order-detail-item__controls">
                            <div>
                                <label class="admin-label">Qty</label>
                                ${isLocked ? `
                                    <div class="field bg-mist text-center font-medium tabular-nums py-1">${item.qty}</div>
                                ` : `
                                    <div class="od-stepper">
                                        <button type="button" data-qty="-1" aria-label="Decrease qty">−</button>
                                        <input data-field="qty" type="number" min="1" value="${item.qty}">
                                        <button type="button" data-qty="1" aria-label="Increase qty">+</button>
                                    </div>
                                `}
                            </div>
                            <div>
                                <label class="admin-label">Price</label>
                                ${isLocked ? `
                                    <div class="field bg-mist text-center font-medium tabular-nums py-1">${money(item.price)}</div>
                                ` : `
                                    <div class="od-stepper">
                                        <button type="button" data-price="-1" aria-label="Decrease price">−</button>
                                        <input data-field="price" type="number" min="0" step="0.01" value="${Number(item.price).toFixed(2)}">
                                        <button type="button" data-price="1" aria-label="Increase price">+</button>
                                    </div>
                                `}
                            </div>
                            <div>
                                <label class="admin-label">Total</label>
                                <input type="text" class="field bg-mist text-center font-medium tabular-nums" value="${plainMoney(lineTotal(item))}" data-line-total readonly>
                            </div>
                        </div>
                    </div>
                `).join('');

                recalculate();
            };

            list.addEventListener('click', (event) => {
                if (isLocked) return;
                const row = event.target.closest('[data-index]');
                if (! row) return;
                const index = Number(row.dataset.index);
                if (event.target.closest('[data-remove]')) {
                    items.splice(index, 1);
                    render();
                    return;
                }
                const qtyBtn = event.target.closest('[data-qty]');
                if (qtyBtn) {
                    items[index].qty = Math.max(1, Number(items[index].qty) + Number(qtyBtn.dataset.qty));
                    render();
                    return;
                }
                const priceBtn = event.target.closest('[data-price]');
                if (priceBtn) {
                    items[index].price = Math.max(0, Number(items[index].price) + Number(priceBtn.dataset.price));
                    render();
                }
            });

            list.addEventListener('input', (event) => {
                if (isLocked) return;
                const row = event.target.closest('[data-index]');
                if (! row || ! event.target.dataset.field) return;
                const index = Number(row.dataset.index);
                const field = event.target.dataset.field;
                let value = event.target.value;
                if (field === 'qty') value = Math.max(1, parseInt(value || '1', 10));
                if (field === 'price') value = Math.max(0, Number(value || 0));
                items[index][field] = value;
                const totalEl = row.querySelector('[data-line-total]');
                if (totalEl) totalEl.value = plainMoney(lineTotal(items[index]));
                recalculate();
            });

            const syncShippingMethodFromAmount = () => {
                if (! shippingSelect || ! shippingInput) {
                    return;
                }

                const current = Number(shippingInput.value || 0).toFixed(2);
                const match = Array.from(shippingSelect.options).find((option) => {
                    const amount = option.dataset.amount;

                    return amount !== undefined && amount !== '' && Number(amount).toFixed(2) === current;
                });

                shippingSelect.value = match ? match.value : '';
            };

            const checkFreeDeliveryRules = () => {
                if (isLocked) return;
                const hasFreeItem = items.some((it) => !!it.free_delivery);
                if (hasFreeItem && shippingInput && Number(shippingInput.value || 0) > 0) {
                    shippingInput.value = '0.00';
                    syncShippingMethodFromAmount();
                }
            };

            if (! isLocked) {
                shippingInput?.addEventListener('input', () => {
                    syncShippingMethodFromAmount();
                    recalculate();
                });
                discountInput?.addEventListener('input', recalculate);
                advanceInput?.addEventListener('input', recalculate);
                if (shippingSelect) {
                    shippingSelect.addEventListener('change', () => {
                        const selected = shippingSelect.selectedOptions[0];
                        const amount = selected?.dataset.amount;

                        if (amount !== undefined && amount !== '') {
                            shippingInput.value = Number(amount).toFixed(2);
                            recalculate();
                        }
                    });
                    checkFreeDeliveryRules();
                    syncShippingMethodFromAmount();
                }
            }

            noteField?.addEventListener('input', () => {
                noteCount.textContent = String(noteField.value.length);
                if (statusNotesSync) statusNotesSync.value = noteField.value;
            });

            document.getElementById('order-status-form')?.addEventListener('submit', () => {
                if (noteField && statusNotesSync) statusNotesSync.value = noteField.value;
            });

            let searchTimer = null;
            let searchResults = [];
            const hideResults = () => resultsBox?.classList.add('hidden');

            if (! isLocked && searchInput && resultsBox) {
                resultsBox.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-product-index]');
                    if (! button) return;
                    const product = searchResults[Number(button.dataset.productIndex)];
                    if (! product) return;
                    items.push({
                        id: null, product_id: product.id, product_name: product.name,
                        sku: product.sku || '', slug: product.slug || '',
                        color: '', size: '', qty: 1, price: product.price, discount: 0,
                        free_delivery: !!product.free_delivery,
                        stock: product.stock, image_url: product.image_url,
                    });
                    searchInput.value = '';
                    hideResults();
                    checkFreeDeliveryRules();
                    render();
                });

                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimer);
                    const q = searchInput.value.trim();
                    searchTimer = setTimeout(async () => {
                        const response = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const json = await response.json();
                        searchResults = json.data || [];
                        if (! searchResults.length) {
                            resultsBox.innerHTML = '<div class="px-2 py-1 text-[10px] text-muted">No products</div>';
                        } else {
                            resultsBox.innerHTML = searchResults.map((product, index) => `
                                <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-[12px] hover:bg-mist" data-product-index="${index}">
                                    <div class="h-8 w-8 shrink-0 overflow-hidden rounded-md bg-mist">${product.image_url ? `<img src="${escapeHtml(product.image_url)}" class="h-full w-full object-cover" alt="">` : ''}</div>
                                    <div class="min-w-0">
                                        <div class="truncate font-semibold">${escapeHtml(product.sku || product.name)}</div>
                                        <div class="truncate text-[11px] text-muted">${escapeHtml(product.name)} · ৳${Number(product.price).toFixed(0)} · Stock ${product.stock}</div>
                                    </div>
                                </button>
                            `).join('');
                        }
                        resultsBox.classList.remove('hidden');
                    }, 220);
                });

                searchInput.addEventListener('blur', () => setTimeout(hideResults, 180));
                document.addEventListener('click', (event) => {
                    if (! event.target.closest('#product-search') && ! event.target.closest('#product-search-results')) hideResults();
                });
            }

            document.querySelectorAll('[data-copy]').forEach((button) => {
                button.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(button.getAttribute('data-copy') || '');
                        button.textContent = 'OK';
                        setTimeout(() => { button.textContent = 'Copy'; }, 800);
                    } catch (e) {}
                });
            });

            const statusSelect = document.getElementById('status');
            const cancelReasonBox = document.getElementById('detail-cancel-reason-box');
            const detailCancelReasonSelect = document.getElementById('detail_cancel_reason');
            const detailCustomReasonInput = document.getElementById('detail_custom_cancel_reason');
            const statusCancelReasonInput = document.getElementById('status-cancel-reason-input');

            statusSelect?.addEventListener('change', () => {
                if (statusSelect.value === 'cancelled') {
                    cancelReasonBox?.classList.remove('hidden');
                } else {
                    cancelReasonBox?.classList.add('hidden');
                }
            });

            detailCancelReasonSelect?.addEventListener('change', () => {
                if (detailCancelReasonSelect.value === 'Other') {
                    detailCustomReasonInput?.classList.remove('hidden');
                } else {
                    detailCustomReasonInput?.classList.add('hidden');
                }
                syncDetailCancelReason();
            });

            detailCustomReasonInput?.addEventListener('input', syncDetailCancelReason);

            function syncDetailCancelReason() {
                let val = detailCancelReasonSelect?.value || '';
                if (val === 'Other' && detailCustomReasonInput && detailCustomReasonInput.value.trim() !== '') {
                    val = detailCustomReasonInput.value.trim();
                }
                if (statusCancelReasonInput) {
                    statusCancelReasonInput.value = val;
                }
            }

            // Single cancel modal handlers
            const singleCancelModal = document.getElementById('single-cancel-modal');
            const modalSingleReason = document.getElementById('modal-single-cancel-reason');
            const modalSingleCustomBox = document.getElementById('modal-single-custom-reason-box');
            const modalSingleCustomInput = document.getElementById('modal-single-custom-reason');
            const modalSingleConfirmBtn = document.getElementById('modal-single-cancel-confirm-btn');

            window.openSingleCancelModal = function() {
                singleCancelModal?.classList.remove('hidden');
            };

            window.closeSingleCancelModal = function() {
                singleCancelModal?.classList.add('hidden');
            };

            modalSingleReason?.addEventListener('change', () => {
                if (modalSingleReason.value === 'Other') {
                    modalSingleCustomBox?.classList.remove('hidden');
                } else {
                    modalSingleCustomBox?.classList.add('hidden');
                }
            });

            modalSingleConfirmBtn?.addEventListener('click', () => {
                let reason = modalSingleReason?.value || '';
                if (!reason) {
                    alert('Please select a cancellation reason.');
                    return;
                }
                if (reason === 'Other' && modalSingleCustomInput && modalSingleCustomInput.value.trim() !== '') {
                    reason = modalSingleCustomInput.value.trim();
                }
                if (statusSelect) statusSelect.value = 'cancelled';
                if (statusCancelReasonInput) statusCancelReasonInput.value = reason;
                singleCancelModal?.classList.add('hidden');
                document.getElementById('order-status-form')?.submit();
            });

            document.getElementById('order-status-form')?.addEventListener('submit', (e) => {
                if (statusSelect && statusSelect.value === 'cancelled') {
                    syncDetailCancelReason();
                    if (!statusCancelReasonInput?.value) {
                        e.preventDefault();
                        alert('Please select a cancellation reason.');
                        detailCancelReasonSelect?.focus();
                    }
                }
            });

            // Bangladesh Location Cascading Dropdowns
            const bdLocations = @json(\App\Support\BangladeshLocations::all());
            const divisionSelect = document.getElementById('delivery_division');
            const districtSelect = document.getElementById('delivery_district');
            const upazilaSelect = document.getElementById('delivery_upazila');

            let selectedDivVal = @json(old('delivery_division', $order->delivery_division));
            let selectedDistVal = @json(old('delivery_district', $order->delivery_district));
            let selectedUpzVal = @json(old('delivery_upazila', $order->delivery_upazila));

            function updateDistricts(divName, preselectedDistrict) {
                if (!districtSelect) return;
                const prev = preselectedDistrict || districtSelect.value;
                districtSelect.innerHTML = '<option value="">Select District</option>';

                let districts = {};
                if (divName && bdLocations[divName]) {
                    districts = bdLocations[divName];
                } else {
                    Object.values(bdLocations).forEach(divObj => {
                        Object.assign(districts, divObj);
                    });
                }

                const names = Object.keys(districts).sort();
                if (prev && !names.some(n => n.toLowerCase() === prev.toLowerCase())) {
                    const customOpt = document.createElement('option');
                    customOpt.value = prev;
                    customOpt.textContent = prev;
                    customOpt.selected = true;
                    districtSelect.appendChild(customOpt);
                }

                names.forEach(distName => {
                    const opt = document.createElement('option');
                    opt.value = distName;
                    opt.textContent = distName;
                    if (prev && distName.toLowerCase() === prev.toLowerCase()) {
                        opt.selected = true;
                    }
                    districtSelect.appendChild(opt);
                });

                updateUpazilas(districtSelect.value, selectedUpzVal);
            }

            function updateUpazilas(distName, preselectedUpazila) {
                if (!upazilaSelect) return;
                const prev = preselectedUpazila || upazilaSelect.value;
                upazilaSelect.innerHTML = '<option value="">Select Upazila</option>';
                if (!distName) return;

                let upazilas = [];
                for (const div in bdLocations) {
                    if (bdLocations[div][distName]) {
                        upazilas = bdLocations[div][distName];
                        if (divisionSelect && !divisionSelect.value) {
                            divisionSelect.value = div;
                        }
                        break;
                    }
                }

                if (prev && !upazilas.some(u => u.toLowerCase() === prev.toLowerCase())) {
                    const customOpt = document.createElement('option');
                    customOpt.value = prev;
                    customOpt.textContent = prev;
                    customOpt.selected = true;
                    upazilaSelect.appendChild(customOpt);
                }

                upazilas.forEach(upzName => {
                    const opt = document.createElement('option');
                    opt.value = upzName;
                    opt.textContent = upzName;
                    if (prev && upzName.toLowerCase() === prev.toLowerCase()) {
                        opt.selected = true;
                    }
                    upazilaSelect.appendChild(opt);
                });
            }

            divisionSelect?.addEventListener('change', () => {
                selectedUpzVal = '';
                updateDistricts(divisionSelect.value, '');
            });

            districtSelect?.addEventListener('change', () => {
                selectedUpzVal = '';
                updateUpazilas(districtSelect.value, '');
            });

            updateDistricts(selectedDivVal, selectedDistVal);

            render();
        })();
    </script>
@endsection
