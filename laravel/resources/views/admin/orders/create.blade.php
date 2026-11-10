@extends('layouts.admin')

@php
    use App\Support\Money;
    use App\Support\BangladeshLocations;
    use App\Support\CourierRatioCards;

    $emptyOurRecord = CourierRatioCards::ourRecordForPhone(null);
    $zeroFraudReport = CourierRatioCards::zeroFraudReport();
    $defaultShipping = $shippingCharges->first();
    $initialShippingAmount = (float) old('shipping_charge', $defaultShipping?->amount ?? 0);
@endphp

@section('title', 'Create Manual Order')
@section('heading')
    <span class="inline-flex items-center gap-2">
        <a href="{{ route('admin.orders.index') }}" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-ink/10 bg-white text-muted shadow-sm hover:bg-mist hover:text-ink" title="Back to orders">
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.56l3.22 3.22a.75.75 0 1 1-1.06 1.06l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 0 1 1.06 1.06L5.56 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
        </a>
        <span>
            Create Manual Order
            <span class="ml-1.5 font-mono text-sm font-normal text-muted">#NEW</span>
        </span>
    </span>
@endsection
@section('subheading', 'Create a manual order received via Phone Call, WhatsApp, Messenger, or SMS.')

@section('actions')
    <button type="button" id="open-ai-modal-btn" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important; color: #ffffff !important; border: 1px solid #4338ca !important;" class="inline-flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-xs font-bold shadow-md transition hover:opacity-95 active:scale-95 cursor-pointer">
        <svg class="h-4 w-4 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
        </svg>
        <span class="font-bold tracking-wide text-white">AI Auto-Fill</span>
    </button>
    <span class="admin-badge-pill bg-amber-50 text-amber-800 border border-amber-200 font-semibold" id="header-payment-pill">UNPAID (COD)</span>
    <span class="admin-badge-pill admin-badge-warning" id="header-status-pill">Pending</span>
    <span class="admin-badge-pill admin-badge-sage" id="header-source-pill">PHONE CALL</span>
@endsection

@section('content')
    <div class="order-detail space-y-1.5">
        {{-- Top row: left Courier ratio (70%) | right Actions (30%) --}}
        <div class="order-detail-top">
            <div class="od-panel order-detail-top__ratio">
                <div class="od-panel-h">
                    <div class="flex items-center gap-2">
                        <h2 class="admin-section-title !mb-0">Courier ratio report</h2>
                        <span id="ratio-loading-spinner" style="display: none;" class="text-xs text-teal-600 font-medium items-center gap-1">
                            <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Checking ratio...
                        </span>
                    </div>
                    <button type="button" id="manual-check-ratio-btn" class="admin-btn bg-teal-600 text-white hover:bg-teal-700">Check Ratio</button>
                </div>
                <div class="od-panel-b !py-1.5">
                    <div id="courier-ratio-box">
                        @include('admin.partials.courier-ratio-cards', [
                            'fraudReport' => $zeroFraudReport,
                            'ourRecord' => $emptyOurRecord,
                            'scrollKey' => 'create-order',
                            'showOurRecord' => true,
                        ])
                    </div>
                    <p id="fraud-reports-summary" class="mt-1 truncate text-[10px] text-muted hidden"></p>
                </div>
            </div>

            <div class="od-panel order-detail-top__actions">
                <div class="od-panel-h">
                    <h2 class="admin-section-title !mb-0">Actions</h2>
                </div>
                <div class="od-panel-b flex h-full flex-col gap-1">
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="admin-label" for="top_order_status">Initial Status</label>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-blue-700">All Orders Lifecycle</span>
                        </div>
                        <select id="top_order_status" class="field" form="order-create-form" name="order_status_id">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" data-slug="{{ $status->slug }}" @selected(old('order_status_id') == $status->id || (!old('order_status_id') && $status->slug === 'pending'))>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="admin-label" for="top_source_platform">Order Source</label>
                        <select id="top_source_platform" class="field" form="order-create-form" name="source_platform">
                            @foreach ($sourcePlatforms as $key => $label)
                                <option value="{{ $key }}" @selected(old('source_platform', 'phone_call') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="min-h-0 flex-1">
                        <label class="admin-label" for="admin_notes">Admin note</label>
                        <textarea id="admin_notes" name="admin_notes" form="order-create-form" rows="2" class="field h-[calc(100%-1rem)] min-h-[3.5rem]" placeholder="Call notes, customer requests...">{{ old('admin_notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                <div class="font-semibold text-red-900 mb-1">Please fix the following:</div>
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main: left 70% (Customer + Products) | right 30% (Summary) --}}
        <div class="order-detail-main">
            <div class="order-detail-main__left">
                <form id="order-create-form" method="POST" action="{{ route('admin.orders.store') }}" class="space-y-1.5">
                    @csrf
                    <input type="hidden" name="action" id="order-action-input" value="save">

                    <div class="od-panel">
                        <div class="od-panel-h">
                            <div class="flex items-center gap-2">
                                <h2 class="admin-section-title !mb-0">Customer &amp; shipping</h2>
                            </div>
                            <button type="submit" onclick="document.getElementById('order-action-input').value='save'" class="admin-btn bg-sage text-white hover:bg-sage/90">Save</button>
                        </div>
                        <div class="od-panel-b grid grid-cols-2 gap-1 sm:grid-cols-4">
                            <div class="col-span-2 sm:col-span-2">
                                <div class="flex items-center justify-between">
                                    <label class="admin-label" for="customer_phone">Mobile *</label>
                                    <span id="phone-feedback" class="text-[10px] text-muted">Type number for live ratio</span>
                                </div>
                                <input id="customer_phone" type="text" name="customer_phone" value="{{ old('customer_phone') }}" class="field" placeholder="01XXXXXXXXX" required autocomplete="off">
                            </div>
                            <div class="col-span-2 sm:col-span-2">
                                <label class="admin-label" for="customer_name">Name *</label>
                                <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name') }}" class="field" placeholder="Customer name" required>
                            </div>
                            <div class="col-span-2 sm:col-span-4">
                                <label class="admin-label" for="customer_address">Address *</label>
                                <textarea id="customer_address" name="customer_address" rows="2" class="field" placeholder="Full address (Area, Thana, District)" required>{{ old('customer_address') }}</textarea>
                            </div>
                            <div class="col-span-2 sm:col-span-4">
                                <label class="admin-label" for="notes">Note <span id="note-count" class="text-muted">0</span>/350</label>
                                <textarea id="notes" name="notes" rows="1" class="field" maxlength="350" placeholder="Customer notes...">{{ old('notes') }}</textarea>
                            </div>
                            <div>
                                <label class="admin-label" for="delivery_division">Division</label>
                                <select id="delivery_division" name="delivery_division" class="field">
                                    <option value="">Select Division</option>
                                    @foreach (BangladeshLocations::divisions() as $divName)
                                        <option value="{{ $divName }}" @selected(old('delivery_division') === $divName)>{{ $divName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="admin-label" for="delivery_district">District</label>
                                <select id="delivery_district" name="delivery_district" class="field">
                                    <option value="">Select District</option>
                                </select>
                            </div>
                            <div>
                                <label class="admin-label" for="delivery_upazila">Upazila</label>
                                <select id="delivery_upazila" name="delivery_upazila" class="field">
                                    <option value="">Select Upazila</option>
                                </select>
                            </div>
                            <div>
                                <label class="admin-label" for="payment_method">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="field" required>
                                    <option value="cod" @selected(old('payment_method', 'cod') === 'cod')>COD</option>
                                    <option value="sslcommerz" @selected(old('payment_method') === 'sslcommerz')>SSLCommerz</option>
                                    <option value="bkash" @selected(old('payment_method') === 'bkash')>bKash</option>
                                    <option value="nagad" @selected(old('payment_method') === 'nagad')>Nagad</option>
                                    <option value="manual" @selected(old('payment_method') === 'manual')>Manual</option>
                                </select>
                            </div>
                            <div>
                                <label class="admin-label" for="payment_status">Payment Status</label>
                                <select id="payment_status" name="payment_status" class="field" required>
                                    <option value="pending" @selected(old('payment_status', 'pending') === 'pending')>Unpaid (Pending)</option>
                                    <option value="paid" @selected(old('payment_status') === 'paid')>Paid</option>
                                    <option value="failed" @selected(old('payment_status') === 'failed')>Failed</option>
                                    <option value="refunded" @selected(old('payment_status') === 'refunded')>Refunded</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="od-panel od-products-panel">
                        <div class="od-panel-b space-y-3">
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

                            <div>
                                <div class="mb-2 flex items-center gap-2">
                                    <h2 class="text-[0.95rem] font-semibold tracking-tight text-ink">Ordered Products</h2>
                                    <span id="items-count" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-violet-100 px-1.5 text-[11px] font-semibold text-violet-700">0</span>
                                </div>
                                <div id="order-items-list" class="space-y-2"></div>
                                <div id="items-hidden-inputs"></div>
                            </div>

                            <div>
                                <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-sky-600">Order total</div>
                                <div class="od-order-total">
                                    <div>
                                        <label class="admin-label" for="discount">Discount</label>
                                        <input id="discount" type="number" name="discount" min="0" step="0.01" value="{{ old('discount', '0.00') }}" class="field">
                                    </div>
                                    <div>
                                        <label class="admin-label" for="advance_amount">Advance</label>
                                        <input id="advance_amount" type="number" min="0" step="0.01" value="0" class="field" placeholder="0">
                                    </div>
                                    <div>
                                        <label class="admin-label" for="summary-subtotal-input">Sub Total</label>
                                        <input id="summary-subtotal-input" type="text" class="field bg-mist" value="0.00" readonly>
                                    </div>
                                    <div>
                                        <label class="admin-label" for="shipping_charge_select">Delivery Area</label>
                                        <div class="od-delivery-box">
                                            <select id="shipping_charge_select" class="od-delivery-box__method" aria-label="Delivery area">
                                                <option value="" data-amount="">Custom</option>
                                                @foreach ($shippingCharges as $charge)
                                                    <option
                                                        value="{{ $charge->id }}"
                                                        data-amount="{{ number_format((float) $charge->amount, 2, '.', '') }}"
                                                        @selected((float) $initialShippingAmount === (float) $charge->amount)
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
                                                    value="{{ $initialShippingAmount }}"
                                                    aria-label="Delivery charge amount"
                                                    required
                                                >
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="admin-label" for="delivery_method">Delivery Method</label>
                                        @if (! empty($courierProviders))
                                            <select id="delivery_method" name="courier_api_id" class="field" aria-label="Delivery method / courier">
                                                <option value="">Select courier</option>
                                                @foreach ($courierProviders as $courier)
                                                    <option value="{{ $courier['id'] }}" @selected((int) old('courier_api_id') === (int) $courier['id'])>
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
                                        <input id="summary-total-input" type="text" class="field border-red-300 bg-red-50 font-semibold text-red-700" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 flex gap-2">
                                <button type="submit" name="action" value="save" id="save-order-btn" class="od-save-btn flex-1">
                                    Save Order (0.00৳)
                                </button>
                                <button type="submit" name="action" value="confirm" onclick="document.getElementById('order-action-input').value='confirm'" class="admin-btn bg-emerald-600 text-white hover:bg-emerald-700 !py-2.5 px-4 font-semibold text-xs whitespace-nowrap">
                                    Confirm Order → Courier
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <aside class="order-detail-main__right space-y-1.5">
                <div class="od-panel">
                    <div class="od-panel-h"><h2 class="admin-section-title">Order summary</h2></div>
                    <div class="od-panel-b">
                        <div class="order-detail-meta"><dt>Invoice</dt><dd><span class="text-muted">Auto after save</span></dd></div>
                        <div class="order-detail-meta"><dt>Order ref</dt><dd>#NEW</dd></div>
                        <div class="order-detail-meta"><dt>Status</dt><dd><span class="admin-badge-pill admin-badge-warning" id="sidebar-status-label">Pending</span></dd></div>
                        <div class="order-detail-meta"><dt>Payment</dt><dd id="sidebar-payment-label">COD · Pending</dd></div>
                        <div class="order-detail-meta"><dt>Date</dt><dd>{{ now()->format('d M Y, g:i A') }}</dd></div>
                        <div class="order-detail-meta"><dt>Subtotal</dt><dd id="sidebar-subtotal">৳0</dd></div>
                        <div class="order-detail-meta"><dt>Delivery</dt><dd id="sidebar-shipping">৳{{ number_format($initialShippingAmount, 0) }}</dd></div>
                        <div class="order-detail-meta"><dt>Discount</dt><dd id="sidebar-discount">৳0</dd></div>
                        <div class="order-detail-meta text-[0.8rem] font-semibold"><dt>Total</dt><dd id="sidebar-total" class="text-sage">৳0</dd></div>
                        <div class="mt-2 space-y-1 border-t border-ink/8 pt-2" id="sidebar-items">
                            <p class="text-[11px] text-muted">No items</p>
                        </div>
                    </div>
                </div>

                <div class="od-panel">
                    <div class="od-panel-h"><h2 class="admin-section-title">Order Channel</h2></div>
                    <div class="od-panel-b space-y-1 text-xs">
                        <div class="order-detail-meta">
                            <dt>Source</dt>
                            <dd class="font-semibold text-ink" id="sidebar-source-name">Phone Call</dd>
                        </div>
                        <div class="order-detail-meta">
                            <dt>Creator</dt>
                            <dd class="text-muted">{{ auth('admin')->user()->name ?? 'Admin' }}</dd>
                        </div>
                        <p class="mt-2 text-[11px] text-muted leading-relaxed">
                            💡 Entering customer phone number will automatically check and load courier delivery success ratio and order history above.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    {{-- AI Magic Order Auto-Fill Modal --}}
    <div id="ai-parse-modal" style="background-color: rgba(15, 23, 42, 0.7); display: none;" class="fixed inset-0 z-50 items-center justify-center p-4 backdrop-blur-sm">
        <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
            {{-- Modal Header --}}
            <div style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;" class="flex items-center justify-between border-b border-indigo-700 px-6 py-4 shadow-md">
                <div class="flex items-center gap-3">
                    <span style="background-color: rgba(255, 255, 255, 0.2);" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-white shadow-inner">
                        <svg class="h-5 w-5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold tracking-wide text-white uppercase">AI Order Auto-Fill</h3>
                        <p style="color: #e0e7ff;" class="text-xs font-normal">Paste message or screenshot (Ctrl+V) — AI will detect customer info & products</p>
                    </div>
                </div>
                <button type="button" id="close-ai-modal-btn" style="color: #ffffff;" class="rounded-lg p-1.5 hover:bg-white/10 transition cursor-pointer" title="Close modal">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label font-bold text-slate-800" for="ai-message-text">Paste Customer Message</label>
                        <textarea
                            id="ai-message-text"
                            rows="6"
                            style="border: 1px solid #cbd5e1; border-radius: 0.5rem;"
                            class="w-full p-2.5 text-xs font-sans text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="e.g.:&#10;Name: Tanvir Ahmed&#10;Phone: 01711223344&#10;Address: House 12, Road 4, Mirpur-10, Dhaka&#10;Panjabi Size XL 2 pcs"
                        ></textarea>
                    </div>
                    <div>
                        <label class="admin-label font-bold text-slate-800">Or Screenshot / Image (Upload or Paste Ctrl+V)</label>
                        <div
                            id="ai-screenshot-dropzone"
                            style="border: 2px dashed #a5b4fc; background-color: #f8faff; height: 9.5rem;"
                            class="relative flex cursor-pointer flex-col items-center justify-center rounded-lg p-3 text-center transition hover:border-indigo-500 hover:bg-indigo-50/50"
                        >
                            <input type="file" id="ai-screenshot-input" accept="image/*" class="hidden">
                            <div id="ai-screenshot-placeholder" class="space-y-1.5">
                                <svg class="mx-auto h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                <p class="text-xs font-semibold text-slate-700">Click to upload or press <strong class="text-indigo-600">Ctrl+V</strong> anywhere to paste</p>
                                <p class="text-[11px] text-slate-500">WhatsApp, Messenger, SMS chat screenshots</p>
                            </div>
                            <div id="ai-screenshot-preview-box" class="hidden relative h-full w-full overflow-hidden rounded bg-slate-100 flex items-center justify-center">
                                <img id="ai-screenshot-preview-img" src="" alt="Screenshot" class="max-h-full max-w-full object-contain">
                                <button type="button" id="ai-remove-screenshot-btn" class="absolute top-1.5 right-1.5 rounded-full bg-red-600 text-white p-1 shadow hover:bg-red-700" title="Remove screenshot">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="ai-parse-feedback" class="min-h-[1.5rem] text-xs"></div>
            </div>

            {{-- Modal Footer --}}
            <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;" class="flex items-center justify-between px-6 py-3.5">
                <button type="button" id="ai-clear-btn" style="background-color: #ffffff; color: #475569; border: 1px solid #cbd5e1;" class="inline-flex items-center justify-center rounded-md px-3.5 py-1.5 text-xs font-semibold shadow-xs transition hover:bg-slate-100 hover:text-slate-800 cursor-pointer">
                    Clear Input
                </button>
                <div class="flex items-center gap-2.5">
                    <button type="button" id="cancel-ai-modal-btn" style="background-color: #ffffff; color: #334155; border: 1px solid #cbd5e1;" class="inline-flex items-center justify-center rounded-md px-4 py-1.5 text-xs font-semibold shadow-xs transition hover:bg-slate-100 hover:text-slate-900 cursor-pointer">
                        Close
                    </button>
                    <button type="button" id="ai-detect-btn" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important; color: #ffffff !important; border: 1px solid #4338ca !important;" class="inline-flex items-center justify-center gap-1.5 rounded-md px-5 py-1.5 text-xs font-bold shadow-md transition hover:opacity-95 active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg id="ai-btn-spinner" style="display: none;" class="h-3.5 w-3.5 animate-spin text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg id="ai-btn-icon" class="h-3.5 w-3.5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                        <span class="text-white font-bold tracking-wide">Auto-Fill Form</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        (() => {
            const searchUrl = @json(route('admin.orders.products.search'));
            const checkRatioUrl = @json(route('admin.orders.check-ratio'));
            const aiParseUrl = @json(route('admin.orders.ai-parse'));
            const csrfToken = @json(csrf_token());

            let items = [];

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
            const customerPhone = document.getElementById('customer_phone');
            const customerName = document.getElementById('customer_name');
            const customerAddress = document.getElementById('customer_address');
            const phoneFeedback = document.getElementById('phone-feedback');
            const courierRatioBox = document.getElementById('courier-ratio-box');
            const ratioSpinner = document.getElementById('ratio-loading-spinner');
            const manualRatioBtn = document.getElementById('manual-check-ratio-btn');
            const fraudSummary = document.getElementById('fraud-reports-summary');

            const topSourceSelect = document.getElementById('top_source_platform');
            const topStatusSelect = document.getElementById('top_order_status');
            const paymentMethodSelect = document.getElementById('payment_method');
            const paymentStatusSelect = document.getElementById('payment_status');

            const headerPaymentPill = document.getElementById('header-payment-pill');
            const headerStatusPill = document.getElementById('header-status-pill');
            const headerSourcePill = document.getElementById('header-source-pill');
            const sidebarPaymentLabel = document.getElementById('sidebar-payment-label');
            const sidebarStatusLabel = document.getElementById('sidebar-status-label');
            const sidebarSourceName = document.getElementById('sidebar-source-name');

            // AI Panel Elements
            const aiMessageText = document.getElementById('ai-message-text');
            const aiScreenshotDropzone = document.getElementById('ai-screenshot-dropzone');
            const aiScreenshotInput = document.getElementById('ai-screenshot-input');
            const aiScreenshotPlaceholder = document.getElementById('ai-screenshot-placeholder');
            const aiScreenshotPreviewBox = document.getElementById('ai-screenshot-preview-box');
            const aiScreenshotPreviewImg = document.getElementById('ai-screenshot-preview-img');
            const aiRemoveScreenshotBtn = document.getElementById('ai-remove-screenshot-btn');
            const aiDetectBtn = document.getElementById('ai-detect-btn');
            const aiClearBtn = document.getElementById('ai-clear-btn');
            const aiBtnSpinner = document.getElementById('ai-btn-spinner');
            const aiBtnIcon = document.getElementById('ai-btn-icon');
            const aiParseFeedback = document.getElementById('ai-parse-feedback');
            const aiParseModal = document.getElementById('ai-parse-modal');
            const openAiModalBtn = document.getElementById('open-ai-modal-btn');
            const closeAiModalBtn = document.getElementById('close-ai-modal-btn');
            const cancelAiModalBtn = document.getElementById('cancel-ai-modal-btn');

            let currentScreenshotBase64 = null;

            const money = (n) => '৳' + Number(n).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            const plainMoney = (n) => Number(n).toFixed(2);
            const lineTotal = (item) => Math.max(0, (Number(item.price) * Number(item.qty)) - Number(item.discount || 0));
            const itemTitle = (item) => item.sku || item.product_name;
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
                    saveBtn.textContent = `Save Order (${orderTotal.toFixed(2)}৳)`;
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
                    list.innerHTML = '<div class="rounded-lg border border-dashed border-ink/15 px-3 py-6 text-center text-[12px] text-muted">No products added yet. Search above or use AI auto-fill to add items.</div>';
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
                                <div class="mt-0.5 truncate text-[11px] text-muted">${escapeHtml(item.product_name)}${item.color ? ` · Color: ${escapeHtml(item.color)}` : ''}${item.size ? ` · Size: ${escapeHtml(item.size)}` : ''}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px]">
                                    <span class="font-medium text-ink">${money(item.price)}</span>
                                    <span class="text-muted">Stock: ${item.stock ?? '—'}</span>
                                </div>
                            </div>
                            <button type="button" data-remove class="order-detail-item__remove" title="Remove">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75V4.5H3.75a.75.75 0 0 0 0 1.5h.55l.7 10.15A2.25 2.25 0 0 0 7.24 18h5.52a2.25 2.25 0 0 0 2.24-2.05l.7-10.15h.55a.75.75 0 0 0 0-1.5H14v-.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM7.5 4.5v-.75c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25V4.5h-5ZM8.5 8a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-1.5 0v-6.5A.75.75 0 0 1 8.5 8Zm3 0a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-1.5 0v-6.5A.75.75 0 0 1 11.5 8Z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                        <div class="order-detail-item__controls">
                            <div>
                                <label class="admin-label">Qty</label>
                                <div class="od-stepper">
                                    <button type="button" data-qty="-1" aria-label="Decrease qty">−</button>
                                    <input data-field="qty" type="number" min="1" value="${item.qty}">
                                    <button type="button" data-qty="1" aria-label="Increase qty">+</button>
                                </div>
                            </div>
                            <div>
                                <label class="admin-label">Price</label>
                                <div class="od-stepper">
                                    <button type="button" data-price="-1" aria-label="Decrease price">−</button>
                                    <input data-field="price" type="number" min="0" step="0.01" value="${Number(item.price).toFixed(2)}">
                                    <button type="button" data-price="1" aria-label="Increase price">+</button>
                                </div>
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
                if (! shippingSelect || ! shippingInput) return;
                const current = Number(shippingInput.value || 0).toFixed(2);
                const match = Array.from(shippingSelect.options).find((option) => {
                    const amount = option.dataset.amount;
                    return amount !== undefined && amount !== '' && Number(amount).toFixed(2) === current;
                });
                shippingSelect.value = match ? match.value : '';
            };

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
                syncShippingMethodFromAmount();
            }

            noteField?.addEventListener('input', () => {
                noteCount.textContent = String(noteField.value.length);
            });

            // Product search
            let searchTimer = null;
            let searchResults = [];
            const hideResults = () => resultsBox?.classList.add('hidden');

            if (searchInput && resultsBox) {
                resultsBox.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-product-index]');
                    if (! button) return;
                    const product = searchResults[Number(button.dataset.productIndex)];
                    if (! product) return;
                    items.push({
                        id: null,
                        product_id: product.id,
                        product_name: product.name,
                        sku: product.sku || '',
                        color: '',
                        size: '',
                        qty: 1,
                        price: product.price,
                        discount: 0,
                        stock: product.stock,
                        image_url: product.image_url,
                    });
                    searchInput.value = '';
                    hideResults();
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
                            resultsBox.innerHTML = '<div class="px-2 py-1.5 text-[11px] text-muted">No products found</div>';
                        } else {
                            resultsBox.innerHTML = searchResults.map((product, index) => `
                                <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-[12px] hover:bg-mist transition border-b border-ink/5 last:border-b-0" data-product-index="${index}">
                                    <div class="h-8 w-8 shrink-0 overflow-hidden rounded-md bg-mist">${product.image_url ? `<img src="${escapeHtml(product.image_url)}" class="h-full w-full object-cover" alt="">` : ''}</div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate font-semibold text-ink">${escapeHtml(product.sku ? product.sku + ' — ' + product.name : product.name)}</div>
                                        <div class="truncate text-[11px] text-muted">৳${Number(product.price).toFixed(0)} · Stock: <span class="${product.stock > 0 ? 'text-emerald-600 font-medium' : 'text-red-500'}">${product.stock}</span></div>
                                    </div>
                                    <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">+ Add</span>
                                </button>
                            `).join('');
                        }
                        resultsBox.classList.remove('hidden');
                    }, 200);
                });

                searchInput.addEventListener('blur', () => setTimeout(hideResults, 200));
                document.addEventListener('click', (event) => {
                    if (! event.target.closest('#product-search') && ! event.target.closest('#product-search-results')) hideResults();
                });
            }

            // Sync top header and sidebar labels
            const syncHeaderPills = () => {
                const method = paymentMethodSelect?.value?.toUpperCase() || 'COD';
                const status = paymentStatusSelect?.value?.toUpperCase() || 'UNPAID';
                if (headerPaymentPill) {
                    headerPaymentPill.textContent = `${status === 'PAID' ? 'PAID' : 'UNPAID'} (${method})`;
                    headerPaymentPill.className = status === 'PAID'
                        ? 'admin-badge-pill bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold'
                        : 'admin-badge-pill bg-amber-50 text-amber-800 border border-amber-200 font-semibold';
                }
                if (sidebarPaymentLabel) {
                    sidebarPaymentLabel.textContent = `${method} · ${status === 'PAID' ? 'Paid' : 'Pending'}`;
                }

                const selStatusOpt = topStatusSelect?.selectedOptions[0];
                const statusName = selStatusOpt ? selStatusOpt.textContent.trim() : 'Pending';
                if (headerStatusPill) headerStatusPill.textContent = statusName;
                if (sidebarStatusLabel) sidebarStatusLabel.textContent = statusName;

                const selSourceOpt = topSourceSelect?.selectedOptions[0];
                const sourceName = selSourceOpt ? selSourceOpt.textContent.split('(')[0].trim() : 'Phone Call';
                if (headerSourcePill) headerSourcePill.textContent = sourceName.toUpperCase();
                if (sidebarSourceName) sidebarSourceName.textContent = sourceName;
            };

            topSourceSelect?.addEventListener('change', syncHeaderPills);
            topStatusSelect?.addEventListener('change', syncHeaderPills);
            paymentMethodSelect?.addEventListener('change', syncHeaderPills);
            paymentStatusSelect?.addEventListener('change', syncHeaderPills);

            // Live Courier Ratio Checker
            const initialZeroRatioHtml = courierRatioBox.innerHTML;
            let ratioTimer = null;
            let lastCheckedPhone = '';

            const resetToZeroRatio = () => {
                courierRatioBox.innerHTML = initialZeroRatioHtml;
                if (phoneFeedback) phoneFeedback.textContent = 'Type number for live ratio';
                if (ratioSpinner) ratioSpinner.style.display = 'none';
                lastCheckedPhone = '';
            };

            const performRatioCheck = async (force = false) => {
                const phone = customerPhone?.value?.trim() || '';
                const digits = phone.replace(/\D/g, '');

                if (! phone || digits.length < 11) {
                    if (! phone || digits.length === 0) {
                        resetToZeroRatio();
                    } else if (force) {
                        alert('Please enter a valid 11-digit mobile number.');
                    }
                    if (ratioSpinner) ratioSpinner.style.display = 'none';
                    return;
                }

                if (! force && phone === lastCheckedPhone) return;

                lastCheckedPhone = phone;
                if (ratioSpinner) ratioSpinner.style.display = 'inline-flex';
                if (phoneFeedback) phoneFeedback.textContent = 'Checking ratio...';

                try {
                    const response = await fetch(`${checkRatioUrl}?phone=${encodeURIComponent(phone)}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await response.json();
                    if (data.ok && data.html) {
                        courierRatioBox.innerHTML = data.html;
                        if (phoneFeedback) {
                            phoneFeedback.textContent = data.score !== null ? `Ratio: ${Number(data.score).toFixed(1)}%` : 'Ratio checked';
                        }
                    }
                } catch (e) {
                    if (phoneFeedback) phoneFeedback.textContent = 'Ratio check error';
                } finally {
                    if (ratioSpinner) ratioSpinner.style.display = 'none';
                }
            };

            customerPhone?.addEventListener('input', () => {
                clearTimeout(ratioTimer);
                const digits = customerPhone.value.replace(/\D/g, '');
                if (digits.length >= 11) {
                    ratioTimer = setTimeout(() => performRatioCheck(false), 300);
                } else if (digits.length === 0) {
                    resetToZeroRatio();
                }
            });

            customerPhone?.addEventListener('blur', () => {
                const digits = customerPhone?.value?.replace(/\D/g, '') || '';
                if (digits.length >= 11) {
                    performRatioCheck(false);
                } else if (digits.length === 0) {
                    resetToZeroRatio();
                }
            });

            manualRatioBtn?.addEventListener('click', () => {
                performRatioCheck(true);
            });

            // AI Order Auto-Fill Logic
            const setScreenshot = (dataUrl) => {
                currentScreenshotBase64 = dataUrl;
                if (dataUrl) {
                    aiScreenshotPreviewImg.src = dataUrl;
                    aiScreenshotPreviewBox.classList.remove('hidden');
                    aiScreenshotPlaceholder.classList.add('hidden');
                } else {
                    aiScreenshotPreviewImg.src = '';
                    aiScreenshotPreviewBox.classList.add('hidden');
                    aiScreenshotPlaceholder.classList.remove('hidden');
                    aiScreenshotInput.value = '';
                }
            };

            aiScreenshotDropzone?.addEventListener('click', (e) => {
                if (e.target.closest('#ai-remove-screenshot-btn')) return;
                aiScreenshotInput?.click();
            });

            aiScreenshotInput?.addEventListener('change', () => {
                const file = aiScreenshotInput.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => setScreenshot(e.target.result);
                    reader.readAsDataURL(file);
                }
            });

            aiRemoveScreenshotBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                setScreenshot(null);
            });

            // Global Ctrl+V clipboard paste handler for screenshots
            window.addEventListener('paste', (e) => {
                const items = e.clipboardData?.items;
                if (! items) return;
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') !== -1) {
                        const blob = items[i].getAsFile();
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            setScreenshot(event.target.result);
                            openAiModal();
                            if (aiParseFeedback) {
                                aiParseFeedback.innerHTML = '<span class="text-violet-700 font-medium">📷 Screenshot pasted from clipboard! Click "Auto-Fill Form" to detect.</span>';
                            }
                        };
                        reader.readAsDataURL(blob);
                        break;
                    }
                }
            });

            // Open & close AI modal handlers
            const openAiModal = () => {
                if (aiParseModal) {
                    aiParseModal.style.display = 'flex';
                }
                setTimeout(() => aiMessageText?.focus(), 50);
            };
            const closeAiModal = () => {
                if (aiParseModal) {
                    aiParseModal.style.display = 'none';
                }
            };

            openAiModalBtn?.addEventListener('click', openAiModal);
            closeAiModalBtn?.addEventListener('click', closeAiModal);
            cancelAiModalBtn?.addEventListener('click', closeAiModal);

            // Close on clicking backdrop
            aiParseModal?.addEventListener('click', (e) => {
                if (e.target === aiParseModal) {
                    closeAiModal();
                }
            });

            // Escape key closes modal
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && aiParseModal && aiParseModal.style.display !== 'none') {
                    closeAiModal();
                }
            });

            aiClearBtn?.addEventListener('click', () => {
                aiMessageText.value = '';
                setScreenshot(null);
                aiParseFeedback.innerHTML = '';
            });

            aiDetectBtn?.addEventListener('click', async () => {
                const text = aiMessageText?.value?.trim() || '';
                if (! text && ! currentScreenshotBase64) {
                    alert('Please paste message text or upload/paste a screenshot.');
                    aiMessageText?.focus();
                    return;
                }

                aiDetectBtn.disabled = true;
                aiBtnSpinner.style.display = 'inline-block';
                aiBtnIcon.classList.add('hidden');
                aiParseFeedback.innerHTML = '<span class="text-violet-700 font-medium inline-flex items-center gap-1.5"><svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> AI is analyzing and detecting order data...</span>';

                try {
                    const payload = {
                        text: text,
                        image_base64: currentScreenshotBase64,
                    };

                    const response = await fetch(aiParseUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });

                    const res = await response.json();

                    if (! res.ok) {
                        aiParseFeedback.innerHTML = `<span class="text-red-600 font-medium">${res.message || 'Error parsing order.'}</span>`;
                        return;
                    }

                    const d = res.data;
                    let detectedCount = 0;

                    if (d.customer_name) {
                        customerName.value = d.customer_name;
                        detectedCount++;
                    }
                    if (d.customer_phone) {
                        customerPhone.value = d.customer_phone;
                        detectedCount++;
                        performRatioCheck(false);
                    }
                    if (d.customer_address) {
                        customerAddress.value = d.customer_address;
                        detectedCount++;
                    }
                    if (d.notes) {
                        noteField.value = d.notes;
                        noteCount.textContent = String(d.notes.length);
                    }
                    if (d.source_platform && topSourceSelect) {
                        topSourceSelect.value = d.source_platform;
                        syncHeaderPills();
                    }
                    if (d.delivery_division && divisionSelect) {
                        divisionSelect.value = d.delivery_division;
                        updateDistricts(d.delivery_division, d.delivery_district || '');
                        if (d.delivery_district && d.delivery_upazila) {
                            updateUpazilas(d.delivery_district, d.delivery_upazila);
                        }
                    } else if (d.delivery_district && districtSelect) {
                        updateDistricts('', d.delivery_district);
                        if (d.delivery_upazila) {
                            updateUpazilas(d.delivery_district, d.delivery_upazila);
                        }
                    }

                    if (d.items && Array.isArray(d.items) && d.items.length > 0) {
                        d.items.forEach((item) => {
                            items.push({
                                id: null,
                                product_id: item.product_id || null,
                                product_name: item.product_name,
                                sku: item.sku || '',
                                color: item.color || '',
                                size: item.size || '',
                                qty: item.qty || 1,
                                price: item.price || 0,
                                discount: 0,
                                stock: item.stock || null,
                                image_url: item.image_url || null,
                            });
                        });
                        render();
                    }

                    aiParseFeedback.innerHTML = `<span class="text-emerald-700 font-semibold inline-flex items-center gap-1"><svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Successfully detected details! (Name, Phone, Address${d.items && d.items.length ? ', ' + d.items.length + ' product(s)' : ''})</span>`;
                    setTimeout(() => {
                        closeAiModal();
                    }, 1200);
                } catch (err) {
                    aiParseFeedback.innerHTML = '<span class="text-red-600 font-medium">Failed to parse order details. Please try again.</span>';
                } finally {
                    aiDetectBtn.disabled = false;
                    aiBtnSpinner.style.display = 'none';
                    aiBtnIcon.classList.remove('hidden');
                }
            });

            // Form validation before submit
            document.getElementById('order-create-form')?.addEventListener('submit', (e) => {
                if (items.length === 0) {
                    e.preventDefault();
                    alert('Please search and add at least one product to the order.');
                    searchInput?.focus();
                }
            });

            // Bangladesh Location Cascading Dropdowns
            const bdLocations = @json(BangladeshLocations::all());
            const divisionSelect = document.getElementById('delivery_division');
            const districtSelect = document.getElementById('delivery_district');
            const upazilaSelect = document.getElementById('delivery_upazila');

            let selectedDivVal = @json(old('delivery_division', ''));
            let selectedDistVal = @json(old('delivery_district', ''));
            let selectedUpzVal = @json(old('delivery_upazila', ''));

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
            syncHeaderPills();
            render();
        })();
    </script>
@endsection
