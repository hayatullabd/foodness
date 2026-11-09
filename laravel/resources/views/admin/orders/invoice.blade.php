<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        use Illuminate\Support\Str;

        $orders = isset($orders) ? $orders : collect([$order]);
        $firstOrder = $orders->first();
        $storeName = \App\Support\StoreSettings::get('store_name', config('app.name', 'Foodness'));
        $storePhone = \App\Support\StoreSettings::get('store_phone', '');
        $storeAddress = \App\Support\StoreSettings::get('store_address', '');
        $storeLogo = \App\Support\StoreSettings::get('store_logo', null);
        $headerTracking = $firstOrder?->tracking_number ?: ($firstOrder?->invoice_no ? '#'.$firstOrder->invoice_no : '');
    @endphp
    <title>Print Studio - Invoice {{ $headerTracking }}</title>
    <style id="print-page-style">
        @page { size: auto; margin: 8mm; }
    </style>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #18181b;
            margin: 0;
            padding: 0;
            background: #e2e8f0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Top Green Studio Header */
        .studio-header {
            background: #059669;
            color: #ffffff;
            padding: 0.65rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .studio-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .studio-back-btn {
            color: #ffffff;
            text-decoration: none;
            font-size: 1.2rem;
            line-height: 1;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .studio-back-btn:hover { background: rgba(255, 255, 255, 0.15); }

        .studio-title {
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .studio-sub {
            font-size: 0.78rem;
            color: #a7f3d0;
            line-height: 1.1;
        }

        .studio-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-btn {
            padding: 0.35rem 0.95rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 9999px;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .tab-btn-active {
            background: #ffffff;
            color: #065f46;
        }

        .tab-btn-inactive {
            color: #ffffff;
            background: transparent;
        }
        .tab-btn-inactive:hover { background: rgba(255, 255, 255, 0.15); }

        .btn-print-top {
            background: #ffffff;
            color: #047857;
            border: 0;
            font-weight: 700;
            cursor: pointer;
            padding: 0.35rem 0.95rem;
            border-radius: 6px;
            font-size: 0.8rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .btn-print-top:hover { background: #f0fdf4; }

        /* Format Subbar */
        .format-bar {
            background: #ffffff;
            border-bottom: 1px solid #cbd5e1;
            padding: 0.5rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
            position: sticky;
            top: 48px;
            z-index: 40;
        }

        .format-options {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .fmt-label {
            font-weight: 600;
            color: #475569;
            margin-right: 0.25rem;
        }

        .fmt-btn {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            padding: 0.3rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .fmt-btn:hover { background: #e2e8f0; }

        .fmt-btn.active {
            background: #059669;
            color: #ffffff;
            border-color: #059669;
        }

        .format-hint {
            color: #64748b;
            font-size: 0.75rem;
        }

        /* Invoice Preview Area */
        .preview-area {
            padding: 1.75rem 1rem 6rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }

        /* Invoice Sheet Base */
        .invoice-sheet {
            background: #ffffff;
            border: 1px solid #d4d4d8;
            border-radius: 4px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
            color: #18181b;
            transition: all 0.2s ease;
        }

        /* Format Variations */
        .invoice-sheet.fmt-a4 {
            width: 100%;
            max-width: 780px;
            padding: 24px 28px;
            font-size: 9pt;
        }

        .invoice-sheet.fmt-a5 {
            width: 100%;
            max-width: 560px;
            padding: 16px 20px;
            font-size: 8pt;
        }

        .invoice-sheet.fmt-3in {
            width: 100%;
            max-width: 320px;
            padding: 12px 14px;
            font-size: 7.5pt;
        }

        /* Invoice Top Header */
        .inv-top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 12px;
        }

        .inv-top-left {
            line-height: 1.35;
        }

        .inv-datetime {
            font-size: 0.85em;
            color: #52525b;
            font-weight: 500;
        }

        .inv-no {
            font-size: 1.05em;
            font-weight: 700;
            color: #09090b;
        }

        .inv-top-right {
            text-align: right;
        }

        .inv-title {
            font-size: 1.45em;
            font-weight: 800;
            letter-spacing: 1px;
            color: #09090b;
            margin: 0 0 2px;
            line-height: 1;
        }

        .inv-meta-line {
            font-size: 0.82em;
            color: #3f3f46;
            line-height: 1.3;
        }

        .inv-meta-label {
            font-weight: 600;
            color: #71717a;
        }

        /* Merchant Block */
        .merchant-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid #71717a;
            border-bottom: 1px solid #71717a;
            margin-bottom: 12px;
        }

        .merchant-logo {
            width: 50px;
            height: 50px;
            border: 1px solid #d4d4d8;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 7pt;
            color: #a1a1aa;
            line-height: 1.1;
            padding: 2px;
            overflow: hidden;
            background: #fafafa;
            flex-shrink: 0;
        }

        .merchant-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .merchant-details {
            flex: 1;
            line-height: 1.3;
        }

        .merchant-name {
            font-size: 1.15em;
            font-weight: 800;
            color: #09090b;
        }

        .merchant-sub {
            font-size: 0.82em;
            color: #52525b;
            margin-top: 1px;
        }

        /* Delivery & Tracking / Barcode Grid */
        .delivery-barcode-grid {
            display: grid;
            grid-template-columns: 1.25fr 1fr;
            gap: 16px;
            align-items: flex-start;
            padding-bottom: 12px;
            border-bottom: 1px solid #71717a;
            margin-bottom: 12px;
        }

        .invoice-sheet.fmt-3in .delivery-barcode-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .deliver-to-box {
            line-height: 1.35;
        }

        .section-tag {
            font-size: 0.75em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #52525b;
            margin-bottom: 4px;
        }

        .customer-name {
            font-size: 1.05em;
            font-weight: 700;
            color: #09090b;
        }

        .customer-phone {
            font-size: 0.95em;
            font-weight: 700;
            color: #09090b;
            margin-top: 1px;
        }

        .customer-address {
            font-size: 0.88em;
            color: #27272a;
            margin-top: 3px;
            line-height: 1.35;
        }

        .customer-note {
            font-size: 0.8em;
            color: #b45309;
            margin-top: 4px;
            padding: 2px 6px;
            background: #fef3c7;
            border-radius: 3px;
            display: inline-block;
        }

        /* Barcode & QR Box */
        .barcode-qr-box {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            text-align: right;
            gap: 6px;
        }

        .invoice-sheet.fmt-3in .barcode-qr-box {
            align-items: center;
            text-align: center;
        }

        .barcode-wrap {
            width: 100%;
            max-width: 240px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .invoice-sheet.fmt-3in .barcode-wrap {
            align-items: center;
        }

        .barcode-digits {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.88em;
            font-weight: 700;
            letter-spacing: 2px;
            color: #09090b;
            margin-top: 2px;
        }

        .qr-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 2px;
        }

        .qr-box {
            border: 1px solid #e4e4e7;
            padding: 2px;
            background: #ffffff;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px;
        }

        .items-table th {
            border-top: 1px solid #71717a;
            border-bottom: 1px solid #71717a;
            padding: 6px 4px;
            font-size: 0.8em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #3f3f46;
        }

        .items-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #e4e4e7;
            vertical-align: top;
        }

        .item-desc {
            text-align: left;
        }

        .item-name {
            font-weight: 700;
            color: #09090b;
            font-size: 0.95em;
        }

        .item-variant {
            font-size: 0.8em;
            color: #52525b;
            margin-top: 1px;
        }

        .item-qty-pill {
            font-size: 0.82em;
            color: #3f3f46;
            margin-top: 2px;
            font-weight: 500;
        }

        .item-amount {
            text-align: right;
            font-weight: 700;
            color: #09090b;
            font-size: 0.95em;
            white-space: nowrap;
        }

        /* Subtotals */
        .totals-block {
            margin-left: auto;
            width: 220px;
            padding: 4px 0 8px;
            font-size: 0.88em;
        }

        .invoice-sheet.fmt-3in .totals-block {
            width: 100%;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            color: #52525b;
        }

        .totals-row.strong {
            font-weight: 700;
            color: #09090b;
        }

        /* Amount To Collect Box */
        .amount-collect-box {
            border: 2px solid #18181b;
            border-radius: 4px;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            margin: 10px 0 14px;
        }

        .collect-label {
            font-size: 0.95em;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #09090b;
        }

        .collect-value {
            font-size: 1.35em;
            font-weight: 900;
            color: #09090b;
            white-space: nowrap;
        }

        /* Footer */
        .inv-footer {
            border-top: 1px solid #71717a;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78em;
            color: #52525b;
        }

        .invoice-sheet.fmt-3in .inv-footer {
            flex-direction: column;
            gap: 4px;
            text-align: center;
        }

        .footer-left {
            font-weight: 600;
            color: #27272a;
        }

        .footer-right {
            color: #71717a;
        }

        /* Bottom Floating Bar */
        .bottom-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #18181b;
            color: #ffffff;
            padding: 0.45rem 0.6rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
            z-index: 50;
        }

        .btn-bottom {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 0;
            padding: 0.4rem 0.9rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
        }
        .btn-bottom:hover { background: rgba(255, 255, 255, 0.22); }

        .btn-bottom-primary {
            background: #059669;
            color: #ffffff;
        }
        .btn-bottom-primary:hover { background: #047857; }

        /* Print Styles */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .studio-header, .format-bar, .bottom-bar {
                display: none !important;
            }
            .preview-area {
                padding: 0 !important;
                gap: 0 !important;
            }
            .invoice-sheet {
                border: 1px solid #71717a !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 auto !important;
                page-break-after: always !important;
                break-after: page !important;
            }
            .invoice-sheet:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
        }
    </style>
</head>
<body>
    {{-- Green Top Bar --}}
    @php
        $orderIdsParam = $orders->pluck('id')->implode(',');
        $hasBookedOrders = $orders->filter(fn ($o) => filled($o->tracking_number))->isNotEmpty();
        $labelUrl = $orders->count() > 1
            ? route('admin.orders.bulk-sticker', ['ids' => $orderIdsParam])
            : ($firstOrder ? route('admin.orders.sticker', $firstOrder) : '#');
    @endphp
    <header class="studio-header">
        <div class="studio-title-wrap">
            <a href="javascript:history.back()" class="studio-back-btn" title="Back">❮</a>
            <div>
                <div class="studio-title">Print Studio</div>
                <div class="studio-sub">{{ $orders->count() > 1 ? count($orders).' Invoices' : 'Consignment '.$headerTracking }}</div>
            </div>
        </div>
        <div class="studio-actions">
            @if ($hasBookedOrders)
                <a href="{{ $labelUrl }}" class="tab-btn tab-btn-inactive">Label</a>
            @endif
            <a href="#" class="tab-btn tab-btn-active">Invoice</a>
            <button type="button" class="btn-print-top" onclick="window.print()">Print</button>
        </div>
    </header>

    {{-- Format Selector Bar --}}
    <div class="format-bar">
        <div class="format-options">
            <span class="fmt-label">Paper Format:</span>
            <button type="button" class="fmt-btn active" id="btn-a4" onclick="setFormat('a4')">A4</button>
            <button type="button" class="fmt-btn" id="btn-a5" onclick="setFormat('a5')">A5</button>
            <button type="button" class="fmt-btn" id="btn-3in" onclick="setFormat('3in')">3 in</button>
        </div>
        <div class="format-hint">
            Ready to print ({{ count($orders) }} invoice{{ count($orders) > 1 ? 's' : '' }})
        </div>
    </div>

    {{-- Invoice Preview Sheets --}}
    <div class="preview-area">
        @foreach ($orders as $order)
            @php
                $trackingCode = (string) ($order->tracking_number ?: $order->invoice_no);
                $spacedTracking = implode(' ', str_split($trackingCode));
                $courier = strtolower((string) ($order->courier_provider ?: 'steadfast'));
                $courierWeb = match ($courier) {
                    'pathao' => 'pathao.com',
                    'redx' => 'redx.com.bd',
                    'carrybee' => 'carrybee.com',
                    default => 'steadfast.com.bd',
                };
                $courierName = $order->courierApi?->displayName() ?: ($courier === 'steadfast' ? 'steadfast' : strtoupper($courier));
                $isPaid = in_array(strtolower((string) $order->payment_status), ['paid', 'completed'], true);
                $note = trim((string) ($order->admin_notes ?: $order->notes ?: ''));
                $districtLabel = collect([$order->delivery_upazila, $order->delivery_district, $order->delivery_division])->filter()->implode(', ');
                $merchantId = $order->courierApi?->api_key ? substr($order->courierApi->api_key, 0, 8) : ($storePhone ?: 'FOODNESS');
                $parcelId = $order->tracking_number ?: '#'.$order->id;
            @endphp
            <div class="invoice-sheet fmt-a4" id="invoice-{{ $order->id }}">
                {{-- Top Row: Date/Time + Invoice No Left | Title + Parcel ID Right --}}
                <div class="inv-top-row">
                    <div class="inv-top-left">
                        <div class="inv-datetime">{{ $order->created_at?->format('d M, Y h:i A') }}</div>
                        <div class="inv-no">Invoice #{{ $order->invoice_no }}</div>
                    </div>
                    <div class="inv-top-right">
                        <h1 class="inv-title">INVOICE</h1>
                        <div class="inv-meta-line">
                            <span class="inv-meta-label">Parcel ID:</span> {{ $parcelId }}
                        </div>
                        <div class="inv-meta-line">
                            <span class="inv-meta-label">Date:</span> {{ $order->created_at?->format('d M, Y') }}
                        </div>
                        <div class="inv-meta-line">
                            <span class="inv-meta-label">Tracking:</span> {{ $order->tracking_number ?: 'Not booked' }}
                        </div>
                    </div>
                </div>

                {{-- Merchant Block --}}
                <div class="merchant-row">
                    <div class="merchant-logo">
                        @if ($storeLogo)
                            <img src="{{ asset('storage/'.$storeLogo) }}" alt="{{ $storeName }}">
                        @else
                            <span>no image<br>found</span>
                        @endif
                    </div>
                    <div class="merchant-details">
                        <div class="merchant-name">{{ $storeName }}</div>
                        <div class="merchant-sub">Merchant ID: {{ $merchantId }}</div>
                        @if ($storePhone)
                            <div class="merchant-sub">Phone: {{ $storePhone }}</div>
                        @endif
                        @if ($storeAddress)
                            <div class="merchant-sub">Address: {{ $storeAddress }}</div>
                        @endif
                    </div>
                </div>

                {{-- Deliver To & Barcode Section --}}
                <div class="delivery-barcode-grid">
                    <div class="deliver-to-box">
                        <div class="section-tag">DELIVER TO</div>
                        <div class="customer-name">{{ $order->customer_name }}</div>
                        <div class="customer-phone">{{ $order->customer_phone }}</div>
                        <div class="customer-address">
                            {{ $order->customer_address }}
                            @if ($districtLabel)
                                <br><span style="color:#52525b; font-weight:500;">{{ $districtLabel }}</span>
                            @endif
                        </div>
                        @if ($note)
                            <div class="customer-note">Note: {{ $note }}</div>
                        @endif
                    </div>

                    <div class="barcode-qr-box">
                        <div class="barcode-wrap">
                            <div style="width:100%; overflow:hidden;">
                                {!! \App\Support\Barcode::code128Svg($trackingCode, 42, 2) !!}
                            </div>
                            <div class="barcode-digits">{{ $spacedTracking }}</div>
                        </div>
                        <div class="qr-row">
                            <div class="qr-box">
                                {!! \App\Support\QrCode::svg($trackingCode, 58) !!}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="item-desc">DESCRIPTION</th>
                            <th class="item-amount">AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            @php
                                $variantText = $item->variantLabel();
                                $lineTotal = $item->price * $item->qty;
                            @endphp
                            <tr>
                                <td class="item-desc">
                                    <div class="item-name">{{ $item->product_name }}</div>
                                    @if ($variantText)
                                        <div class="item-variant">{{ $variantText }}</div>
                                    @endif
                                    <div class="item-qty-pill">Qty: {{ $item->qty }} &times; ৳{{ number_format((float) $item->price, 2) }}</div>
                                </td>
                                <td class="item-amount">
                                    ৳{{ number_format((float) $lineTotal, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Subtotals --}}
                <div class="totals-block">
                    <div class="totals-row">
                        <span>Subtotal</span>
                        <span>৳{{ number_format((float) $order->subtotal, 2) }}</span>
                    </div>
                    <div class="totals-row">
                        <span>Delivery</span>
                        <span>৳{{ number_format((float) $order->shipping_charge, 2) }}</span>
                    </div>
                    @if ((float) $order->discount > 0)
                        <div class="totals-row">
                            <span>Discount</span>
                            <span>-৳{{ number_format((float) $order->discount, 2) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Amount to Collect Highlight Box --}}
                <div class="amount-collect-box">
                    <div class="collect-label">
                        {{ $isPaid ? 'AMOUNT COLLECTED (PAID)' : 'AMOUNT TO COLLECT' }}
                    </div>
                    <div class="collect-value">
                        ৳ {{ number_format((float) ($isPaid ? 0 : $order->total), 2) }}
                    </div>
                </div>

                {{-- Footer --}}
                <div class="inv-footer">
                    <div class="footer-left">
                        Thank you for your order.
                    </div>
                    <div class="footer-right">
                        Powered by {{ $courierName }} {{ $courierWeb }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Bottom Floating Actions --}}
    <div class="bottom-bar">
        <button type="button" class="btn-bottom" onclick="window.print()" title="Save as PDF via printer">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            PDF
        </button>
        <button type="button" class="btn-bottom" onclick="shareInvoice()" title="Share Invoice URL">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            Share
        </button>
        <button type="button" class="btn-bottom btn-bottom-primary" onclick="window.print()">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Invoice
        </button>
    </div>

    <script>
        function setFormat(fmt) {
            document.querySelectorAll('.fmt-btn').forEach(function (btn) {
                btn.classList.remove('active');
            });
            var activeBtn = document.getElementById('btn-' + fmt);
            if (activeBtn) activeBtn.classList.add('active');

            var sheets = document.querySelectorAll('.invoice-sheet');
            sheets.forEach(function (sheet) {
                sheet.classList.remove('fmt-a4', 'fmt-a5', 'fmt-3in');
                sheet.classList.add('fmt-' + fmt);
            });

            var printStyle = document.getElementById('print-page-style');
            if (fmt === '3in') {
                printStyle.innerHTML = '@page { size: 80mm auto; margin: 3mm; }';
            } else if (fmt === 'a5') {
                printStyle.innerHTML = '@page { size: A5 portrait; margin: 6mm; }';
            } else {
                printStyle.innerHTML = '@page { size: A4 portrait; margin: 8mm; }';
            }

            try {
                localStorage.setItem('foodness_invoice_format', fmt);
            } catch (e) {}
        }

        function shareInvoice() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: window.location.href
                }).catch(function () {});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(window.location.href).then(function () {
                    alert('Invoice link copied to clipboard!');
                }).catch(function () {
                    prompt('Copy this link:', window.location.href);
                });
            } else {
                prompt('Copy this link:', window.location.href);
            }
        }

        // Restore saved format preference if present
        try {
            var saved = localStorage.getItem('foodness_invoice_format');
            if (saved && ['a4', 'a5', '3in'].indexOf(saved) !== -1) {
                setFormat(saved);
            }
        } catch (e) {}
    </script>
</body>
</html>
