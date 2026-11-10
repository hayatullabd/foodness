<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $orders = isset($orders) ? $orders : collect([$order]);
        $firstOrder = $orders->first();
        $storeName = \App\Support\StoreSettings::get('store_name', config('app.name', 'Foodness'));
        $storePhone = \App\Support\StoreSettings::get('store_phone', '');
        $storeLogo = \App\Support\StoreSettings::get('store_logo', null);
    @endphp
    <title>Print Studio - Consignment #{{ $firstOrder?->tracking_number ?: $firstOrder?->invoice_no }}</title>
    <style id="print-page-style">
        @page {
            size: auto;
            margin: 0;
        }
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
            padding: 0.65rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }
        .studio-back-btn:hover { background: rgba(255, 255, 255, 0.15); }

        .studio-title {
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .studio-sub {
            font-size: 0.75rem;
            color: #a7f3d0;
            line-height: 1.1;
        }

        .studio-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-btn {
            padding: 0.35rem 0.85rem;
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

        .btn-print-now {
            background: #ffffff;
            color: #047857;
            border: 0;
            font-weight: 700;
            cursor: pointer;
            padding: 0.35rem 0.9rem;
            border-radius: 6px;
            font-size: 0.8rem;
        }
        .btn-print-now:hover { background: #f0fdf4; }

        /* Format Subbar */
        .format-bar {
            background: #ffffff;
            border-bottom: 1px solid #cbd5e1;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
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
            padding: 0.3rem 0.75rem;
            font-size: 0.78rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
        }

        .fmt-btn.active {
            background: #059669;
            color: #ffffff;
            border-color: #059669;
        }

        .format-hint {
            color: #64748b;
            font-size: 0.75rem;
        }

        /* Stickers Preview Wrapper */
        .preview-area {
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        /* The Exact Sticker Card */
        .sticker-card {
            width: 320px;
            background: #ffffff;
            border: 1.5px solid #52525b;
            border-radius: 4px;
            padding: 10px 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            font-size: 8.5pt;
            color: #18181b;
        }

        .sticker-card.fmt-2x3 {
            width: 260px;
            font-size: 7.5pt;
            padding: 8px 10px;
        }

        /* Card Header */
        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .logo-box {
            width: 52px;
            height: 52px;
            border: 1px solid #d4d4d8;
            border-radius: 6px;
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
        }

        .logo-box img {
            max-width: 100%;
            max-height: 100%;
            object-contain: contain;
        }

        .merchant-info {
            text-align: right;
        }

        .merchant-name {
            font-size: 11.5pt;
            font-weight: 800;
            color: #09090b;
            line-height: 1.2;
        }

        .merchant-id {
            font-size: 7pt;
            color: #52525b;
            margin-top: 2px;
        }

        /* Barcode Area */
        .barcode-section {
            margin: 6px 0 4px;
            text-align: center;
        }

        .barcode-svg-wrap {
            width: 100%;
            overflow: hidden;
        }

        .barcode-number-spaced {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 9.5pt;
            font-weight: 700;
            letter-spacing: 3px;
            margin-top: 3px;
            color: #09090b;
        }

        .divider {
            height: 1px;
            background: #71717a;
            margin: 6px 0;
        }

        /* QR + Meta Table */
        .qr-meta-row {
            display: grid;
            grid-template-columns: 70px 1fr;
            gap: 10px;
            align-items: center;
            padding: 2px 0;
        }

        .qr-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .meta-table td {
            padding: 2px 0;
            vertical-align: middle;
        }

        .meta-label {
            color: #71717a;
            font-weight: 600;
            width: 45%;
            letter-spacing: 0.5px;
        }

        .meta-value {
            font-weight: 700;
            text-align: right;
            color: #18181b;
        }

        /* Customer & Order Details Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-top: 2px;
        }

        .details-table td {
            padding: 2.5px 0;
            vertical-align: top;
            line-height: 1.25;
        }

        .det-label {
            color: #71717a;
            font-weight: 600;
            width: 65px;
            letter-spacing: 0.5px;
        }

        .det-value {
            color: #09090b;
            font-weight: 600;
        }

        .det-value.phone {
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        .det-value.note {
            font-style: italic;
            font-weight: 500;
            color: #4b5563;
        }

        /* Cash On Delivery Box */
        .cod-box {
            border: 1.5px solid #18181b;
            border-radius: 3px;
            padding: 6px 10px;
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
        }

        .cod-title {
            font-size: 9.5pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #18181b;
        }

        .cod-amount {
            font-size: 11.5pt;
            font-weight: 900;
            color: #18181b;
        }

        /* Card Footer */
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
            font-size: 6.8pt;
            color: #71717a;
        }

        .courier-brand {
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .courier-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #059669;
            display: inline-block;
        }

        .courier-bold {
            font-weight: 700;
            color: #0f172a;
        }

        /* Print Media Styles */
        @media print {
            html, body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                height: auto !important;
            }
            .studio-header, .format-bar {
                display: none !important;
            }
            .preview-area {
                padding: 0 !important;
                margin: 0 !important;
                gap: 0 !important;
                display: block !important;
                width: 100% !important;
            }
            .sticker-card {
                width: 100% !important;
                max-width: 100% !important;
                border: 1.5px solid #000000 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 3mm 4mm !important;
                box-sizing: border-box !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: always !important;
                break-after: page !important;
            }
            .sticker-card:last-child {
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
        $invoiceUrl = $orders->count() > 1
            ? route('admin.orders.bulk-invoice', ['ids' => $orderIdsParam])
            : ($firstOrder ? route('admin.orders.invoice', $firstOrder) : '#');
    @endphp
    <header class="studio-header">
        <div class="studio-title-wrap">
            <a href="javascript:history.back()" class="studio-back-btn" title="Back">❮</a>
            <div>
                <div class="studio-title">Print Studio</div>
                <div class="studio-sub">{{ $orders->count() > 1 ? count($orders).' Stickers' : 'Consignment #'.($firstOrder?->tracking_number ?: $firstOrder?->invoice_no) }}</div>
            </div>
        </div>
        <div class="studio-actions">
            <a href="#" class="tab-btn tab-btn-active">Label</a>
            <a href="{{ $invoiceUrl }}" class="tab-btn tab-btn-inactive">Invoice</a>
            <button type="button" class="btn-print-now" onclick="window.print()">Print</button>
        </div>
    </header>

    {{-- Format Selector Bar --}}
    <div class="format-bar">
        <div class="format-options">
            <span class="fmt-label">Paper Format:</span>
            <button type="button" class="fmt-btn active" id="btn-3x3" onclick="setFormat('3x3')">3 × 3 in</button>
            <button type="button" class="fmt-btn" id="btn-2x3" onclick="setFormat('2x3')">2 × 3 in</button>
        </div>
        <div class="format-hint">
            Ready to print ({{ count($orders) }} sticker{{ count($orders) > 1 ? 's' : '' }})
        </div>
    </div>

    {{-- Stickers Preview --}}
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
                $area = collect([$order->delivery_upazila, $order->delivery_district])->filter()->implode(', ') ?: ($order->delivery_division ?: 'Dhaka');
                $itemsSummary = $order->items->map(function ($item) {
                    $name = $item->product_name;
                    $variant = $item->variantLabel();
                    if ($variant && (str_contains($variant, 'order_id:') || str_contains($variant, 'product_id:') || str_contains($variant, 'created_at:') || str_starts_with($variant, 'id:'))) {
                        $variant = null;
                    }
                    $label = $variant ? "{$name} ({$variant})" : $name;
                    return Str::limit($label, 45) . ($item->qty > 1 ? " x {$item->qty}" : '');
                })->implode(', ');
                $merchantId = $order->courierApi?->api_key ? substr($order->courierApi->api_key, 0, 8) : ($storePhone ?: 'FOODNESS');
            @endphp
            <div class="sticker-card" id="sticker-{{ $order->id }}">
                {{-- Top: Logo Left | Merchant Right --}}
                <div class="card-top">
                    <div class="logo-box">
                        @if ($storeLogo)
                            <img src="{{ asset('storage/'.$storeLogo) }}" alt="{{ $storeName }}">
                        @else
                            <span>no image<br>found</span>
                        @endif
                    </div>
                    <div class="merchant-info">
                        <div class="merchant-name">{{ $storeName }}</div>
                        <div class="merchant-id">Merchant ID: {{ $merchantId }}</div>
                    </div>
                </div>

                {{-- Barcode Section --}}
                <div class="barcode-section">
                    <div class="barcode-svg-wrap">
                        {!! \App\Support\Barcode::code128Svg($trackingCode, 44, 2) !!}
                    </div>
                    <div class="barcode-number-spaced">{{ $spacedTracking }}</div>
                </div>

                <div class="divider"></div>

                {{-- QR Code + Meta Info --}}
                <div class="qr-meta-row">
                    <div class="qr-wrap">
                        {!! \App\Support\QrCode::svg($trackingCode, 66) !!}
                    </div>
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">INVOICE</td>
                            <td class="meta-value">{{ $order->invoice_no }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">DELIVERY</td>
                            <td class="meta-value">Home</td>
                        </tr>
                        <tr>
                            <td class="meta-label">WEIGHT</td>
                            <td class="meta-value">{{ (float) ($order->weight ?? $order->estimatedWeightKg()) }} KG</td>
                        </tr>
                    </table>
                </div>

                <div class="divider"></div>

                {{-- Recipient Details --}}
                <table class="details-table">
                    <tr>
                        <td class="det-label">NAME</td>
                        <td class="det-value">{{ $order->customer_name }}</td>
                    </tr>
                    <tr>
                        <td class="det-label">PHONE</td>
                        <td class="det-value phone">{{ $order->customer_phone }}</td>
                    </tr>
                    <tr>
                        <td class="det-label">ADDRESS</td>
                        <td class="det-value">{{ $order->customer_address }}</td>
                    </tr>
                    <tr>
                        <td class="det-label">AREA</td>
                        <td class="det-value">{{ $area }}</td>
                    </tr>
                    <tr>
                        <td class="det-label">ITEM</td>
                        <td class="det-value">{{ $itemsSummary ?: 'General Merchandise' }}</td>
                    </tr>
                    @if ($note !== '')
                        <tr>
                            <td class="det-label">NOTE</td>
                            <td class="det-value note">{{ $note }}</td>
                        </tr>
                    @endif
                </table>

                {{-- COD Box --}}
                <div class="cod-box">
                    <div class="cod-title">{{ $isPaid ? 'PRE-PAID' : 'CASH ON DELIVERY' }}</div>
                    <div class="cod-amount">{{ $isPaid ? 'PAID' : '৳ '.number_format($order->total, 0) }}</div>
                </div>

                {{-- Footer --}}
                <div class="card-footer">
                    <div>Printed: {{ now()->format('d/m/y h:ia') }}</div>
                    <div class="courier-brand">
                        <span class="courier-dot"></span>
                        <span class="courier-bold">{{ $courierName }}</span>
                        <span>{{ $courierWeb }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function setFormat(fmt) {
            document.getElementById('btn-3x3').classList.toggle('active', fmt === '3x3');
            document.getElementById('btn-2x3').classList.toggle('active', fmt === '2x3');

            document.querySelectorAll('.sticker-card').forEach(function(card) {
                if (fmt === '2x3') {
                    card.classList.add('fmt-2x3');
                } else {
                    card.classList.remove('fmt-2x3');
                }
            });

            var printStyle = document.getElementById('print-page-style');
            if (printStyle) {
                if (fmt === '2x3') {
                    printStyle.innerHTML = '@page { size: 2in auto; margin: 0; }';
                } else {
                    printStyle.innerHTML = '@page { size: auto; margin: 0; }';
                }
            }
        }
    </script>
</body>
</html>
