@extends('layouts.admin')

@php
    use App\Support\Money;

    $maxDailySales = max(1, (float) collect($metrics['trendSeries'] ?? [])->max('sales'));
@endphp

@section('title', 'Reports & Analytics')
@section('heading', 'Reports & Analytics')
@section('subheading', $rangeLabel)

@section('actions')
    {{-- Trigger Modal Button --}}
    <button
        type="button"
        onclick="window.openReportAiModal()"
        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-white shadow-xs transition active:scale-95 cursor-pointer hover:opacity-95"
        style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff !important;"
        title="Open Gemini AI Business Intelligence & Strategic Digest"
    >
        <svg class="h-3.5 w-3.5 shrink-0 text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
        </svg>
        <span>✨ Gemini AI Business Intelligence</span>
    </button>

    <a
        href="{{ route('admin.reports.export', ['range' => $activeRange, 'from' => $startDate, 'to' => $endDate]) }}"
        class="admin-btn-secondary !px-2.5 !py-1.5 text-xs inline-flex items-center gap-1 shadow-xs font-medium"
        title="Download full orders report in CSV/Excel format"
    >
        <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        <span>Export CSV</span>
    </a>

    <button
        type="button"
        onclick="window.print()"
        class="admin-btn-secondary !px-2.5 !py-1.5 text-xs inline-flex items-center gap-1 shadow-xs font-medium"
        title="Print or save as PDF"
    >
        <svg class="h-3.5 w-3.5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.076-.67-2.03-1.27-2.829a8.97 8.97 0 01-1.45-3.045C3.38 5.415 4.9 3.5 6.945 3.5h10.11c2.045 0 3.565 1.915 2.945 4.455-.32 1.3-.87 2.37-1.45 3.045-.6.799-1.03 1.753-1.27 2.829M6 16.5h12M9 20.5h6" />
        </svg>
        <span class="hidden sm:inline">Print</span>
    </button>
@endsection

@section('content')
<div class="space-y-2.5 sm:space-y-3">

    {{-- Compact Date Range Filter Bar --}}
    <div class="admin-card p-2 sm:p-2.5 bg-white shadow-xs border border-ink/10 rounded-xl">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-2">
            {{-- Quick Presets --}}
            <div class="flex flex-wrap items-center gap-1 text-[11px]">
                <span class="font-bold text-muted mr-1 flex items-center gap-1 text-[10px] uppercase tracking-wider">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Period:
                </span>
                @foreach ([
                    'today' => 'Today',
                    'yesterday' => 'Yesterday',
                    '7_days' => 'Last 7 Days',
                    '30_days' => 'Last 30 Days',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'this_year' => 'This Year',
                ] as $key => $label)
                    <a
                        href="{{ route('admin.reports.index', ['range' => $key]) }}"
                        @class([
                            'px-2 py-1 rounded-md font-medium transition cursor-pointer text-[11px]',
                            'bg-ink text-white font-bold shadow-xs' => $activeRange === $key,
                            'bg-fog/50 text-ink/80 hover:bg-fog hover:text-ink' => $activeRange !== $key,
                        ])
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Range Form --}}
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-1 w-full lg:w-auto">
                <input type="hidden" name="range" value="custom">
                <input
                    type="date"
                    name="from"
                    value="{{ $startDate }}"
                    max="{{ now()->toDateString() }}"
                    class="field !py-0.5 !px-1.5 !text-[11px] bg-white w-28 border-ink/20"
                    title="From date"
                    required
                />
                <span class="text-[10px] text-muted font-bold">to</span>
                <input
                    type="date"
                    name="to"
                    value="{{ $endDate }}"
                    max="{{ now()->toDateString() }}"
                    class="field !py-0.5 !px-1.5 !text-[11px] bg-white w-28 border-ink/20"
                    title="To date"
                    required
                />
                <button
                    type="submit"
                    class="admin-btn-secondary !py-0.5 !px-2 text-[11px] font-semibold shrink-0"
                >
                    Filter
                </button>
            </form>
        </div>
    </div>

    {{-- 4 Compact KPI Cards --}}
    <div class="grid grid-cols-2 gap-2 sm:gap-2.5 xl:grid-cols-4">
        {{-- Gross Sales --}}
        <div class="admin-card p-2.5 sm:p-3 bg-white border border-ink/10 shadow-xs rounded-xl flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] sm:text-[11px] font-bold text-muted uppercase tracking-wider">Gross Sales</span>
                <span class="flex h-5 w-5 sm:h-6 sm:w-6 items-center justify-center rounded bg-emerald-50 text-emerald-700 font-bold text-xs">৳</span>
            </div>
            <div class="text-lg sm:text-xl font-black text-ink tracking-tight">
                {{ Money::format($metrics['grossSales']) }}
            </div>
            <div class="mt-1.5 text-[10px] text-muted flex items-center justify-between pt-1 border-t border-ink/5">
                <span>Fulfilled Orders:</span>
                <span class="font-bold text-ink">{{ number_format($metrics['revenueOrdersCount']) }} orders</span>
            </div>
        </div>

        {{-- Net Revenue / Subtotal --}}
        <div class="admin-card p-2.5 sm:p-3 bg-white border border-ink/10 shadow-xs rounded-xl flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] sm:text-[11px] font-bold text-muted uppercase tracking-wider">Product Net Sales</span>
                <span class="flex h-5 w-5 sm:h-6 sm:w-6 items-center justify-center rounded bg-sky-50 text-sky-700 text-xs">📦</span>
            </div>
            <div class="text-lg sm:text-xl font-black text-ink tracking-tight">
                {{ Money::format($metrics['netSubtotal']) }}
            </div>
            <div class="mt-1.5 text-[10px] text-muted flex items-center justify-between pt-1 border-t border-ink/5">
                <span>Ship: +{{ Money::format($metrics['shippingCollected']) }}</span>
                <span>Disc: -{{ Money::format($metrics['discountsGiven']) }}</span>
            </div>
        </div>

        {{-- Total Orders & Delivery Success --}}
        <div class="admin-card p-2.5 sm:p-3 bg-white border border-ink/10 shadow-xs rounded-xl flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] sm:text-[11px] font-bold text-muted uppercase tracking-wider">Orders & Deliv Rate</span>
                <span class="flex h-5 w-5 sm:h-6 sm:w-6 items-center justify-center rounded bg-indigo-50 text-indigo-700 text-xs">🚚</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-lg sm:text-xl font-black text-ink tracking-tight">{{ number_format($metrics['totalOrders']) }}</span>
                <span class="text-[11px] font-bold text-emerald-700">({{ $metrics['deliveryRate'] }}% Deliv)</span>
            </div>
            <div class="mt-1.5 text-[10px] text-muted flex items-center justify-between pt-1 border-t border-ink/5">
                <span class="text-emerald-700 font-semibold">{{ $metrics['deliveredCount'] }} delivered</span>
                <span class="text-rose-600 font-semibold">{{ $metrics['returnedCount'] }} return ({{ $metrics['returnRate'] }}%)</span>
            </div>
        </div>

        {{-- Average Order Value (AOV) --}}
        <div class="admin-card p-2.5 sm:p-3 bg-white border border-ink/10 shadow-xs rounded-xl flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] sm:text-[11px] font-bold text-muted uppercase tracking-wider">Avg Order Value (AOV)</span>
                <span class="flex h-5 w-5 sm:h-6 sm:w-6 items-center justify-center rounded bg-amber-50 text-amber-700 text-xs">📊</span>
            </div>
            <div class="text-lg sm:text-xl font-black text-ink tracking-tight">
                {{ Money::format($metrics['aov']) }}
            </div>
            <div class="mt-1.5 text-[10px] text-muted flex items-center justify-between pt-1 border-t border-ink/5">
                <span>Per active customer order</span>
                <span class="text-emerald-600 font-bold">Standard</span>
            </div>
        </div>
    </div>

    {{-- Compact Sales Trend Bar Chart --}}
    <div class="admin-card p-3 sm:p-3.5 bg-white border border-ink/10 shadow-xs rounded-xl">
        <div class="flex items-center justify-between mb-2 border-b border-ink/10 pb-1.5">
            <div>
                <h3 class="text-xs sm:text-sm font-bold text-ink">Sales & Orders Trend Timeline</h3>
                <p class="text-[10px] text-muted">
                    @if($activeRange === 'today' || $activeRange === 'yesterday')
                        Hourly visual trend of revenue & orders (3-hour intervals)
                    @else
                        Daily visual trend of revenue and completed orders
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-sage">Total: {{ Money::format($metrics['grossSales']) }}</span>
                <button
                    type="button"
                    onclick="window.openReportAiModal()"
                    class="hidden sm:inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 hover:text-indigo-900 bg-indigo-50 px-2 py-0.5 rounded-md transition"
                >
                    <span>✨ AI Analysis</span>
                </button>
            </div>
        </div>

        @if(count($metrics['trendSeries']) > 0)
            <div class="relative mt-2">
                {{-- Horizontal Background Grid Lines --}}
                <div class="absolute inset-0 flex flex-col justify-between pointer-events-none pb-5 opacity-40">
                    <div class="border-b border-dashed border-slate-200 w-full"></div>
                    <div class="border-b border-dashed border-slate-200 w-full"></div>
                    <div class="border-b border-slate-200 w-full"></div>
                </div>

                {{-- Bars Container --}}
                <div class="relative z-10 flex items-end gap-1.5 sm:gap-2 h-32 sm:h-36 overflow-x-auto pb-1 no-scrollbar">
                    @foreach($metrics['trendSeries'] as $point)
                        @php
                            $barHeight = $maxDailySales > 0 ? max(($point['sales'] > 0 ? 6 : 2), round(($point['sales'] / $maxDailySales) * 100)) : 2;
                        @endphp
                        <div class="flex-1 min-w-[28px] sm:min-w-[36px] flex flex-col items-center justify-end h-full group relative">
                            {{-- Tooltip on Hover --}}
                            <div class="absolute bottom-full mb-1.5 hidden group-hover:flex flex-col items-center z-30 pointer-events-none">
                                <div class="bg-slate-900 text-white text-[10px] rounded-lg px-2.5 py-1.5 shadow-xl whitespace-nowrap text-center border border-slate-700">
                                    <div class="font-bold text-indigo-200">{{ $point['label'] }}</div>
                                    <div class="font-semibold text-emerald-400">{{ Money::format($point['sales']) }}</div>
                                    <div class="text-slate-300 text-[9px]">{{ $point['orders'] }} {{ $point['orders'] === 1 ? 'order' : 'orders' }}</div>
                                </div>
                                <div class="w-1.5 h-1.5 bg-slate-900 rotate-45 -mt-0.5"></div>
                            </div>

                            {{-- Pillar Track & Active Bar --}}
                            <div class="w-full max-w-[22px] sm:max-w-[28px] h-full flex items-end justify-center">
                                <div class="w-full h-full bg-slate-100/80 rounded-t-md flex items-end overflow-hidden group-hover:bg-indigo-50 transition">
                                    <div
                                        class="w-full rounded-t-md transition-all duration-300 {{ $point['sales'] > 0 ? 'bg-gradient-to-t from-indigo-700 to-indigo-500 shadow-xs' : 'bg-slate-200/50' }}"
                                        style="height: {{ $barHeight }}%;"
                                    ></div>
                                </div>
                            </div>

                            {{-- X-Axis Label --}}
                            <span class="text-[9px] text-muted truncate mt-1.5 group-hover:text-indigo-900 font-medium">
                                {{ $point['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="py-8 text-center text-xs text-muted">
                No sales data recorded for this time period.
            </div>
        @endif
    </div>

    {{-- Two Columns: Order Status & Payment Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2.5 sm:gap-3">
        {{-- Status Breakdown --}}
        <div class="admin-card p-3 sm:p-3.5 bg-white border border-ink/10 shadow-xs rounded-xl space-y-2">
            <h3 class="text-xs sm:text-sm font-bold text-ink border-b border-ink/10 pb-1.5 flex items-center justify-between">
                <span>Order Status Distribution</span>
                <span class="text-[11px] text-muted font-normal">Total: {{ $metrics['totalOrders'] }}</span>
            </h3>

            @php
                $statusRows = [
                    ['label' => 'Delivered / Completed', 'count' => $metrics['deliveredCount'], 'color' => 'bg-emerald-500'],
                    ['label' => 'In Courier / On the Way', 'count' => $metrics['inCourierCount'], 'color' => 'bg-sky-500'],
                    ['label' => 'Processing', 'count' => $metrics['processingCount'], 'color' => 'bg-amber-500'],
                    ['label' => 'Pending Confirmation', 'count' => $metrics['pendingCount'], 'color' => 'bg-slate-400'],
                    ['label' => 'Cancelled & Returned', 'count' => $metrics['returnedCount'], 'color' => 'bg-rose-500'],
                ];
            @endphp

            <div class="space-y-2 pt-0.5">
                @foreach($statusRows as $row)
                    @php
                        $pct = $metrics['totalOrders'] > 0 ? round(($row['count'] / $metrics['totalOrders']) * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-[11px] mb-0.5">
                            <span class="font-medium text-slate-700">{{ $row['label'] }}</span>
                            <span class="font-bold text-ink">{{ $row['count'] }} orders ({{ $pct }}%)</span>
                        </div>
                        <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full {{ $row['color'] }} rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Payment Method Breakdown --}}
        <div class="admin-card p-3 sm:p-3.5 bg-white border border-ink/10 shadow-xs rounded-xl space-y-2">
            <h3 class="text-xs sm:text-sm font-bold text-ink border-b border-ink/10 pb-1.5 flex items-center justify-between">
                <span>Payment Channels & Collection</span>
                <span class="text-[11px] text-muted font-normal">Active Methods</span>
            </h3>

            @if(count($metrics['paymentCounts']) > 0)
                <div class="divide-y divide-ink/5">
                    @foreach($metrics['paymentCounts'] as $method => $data)
                        @php
                            $methodName = match($method) {
                                'cod' => 'Cash on Delivery (COD)',
                                'bkash' => 'bKash Online Payment',
                                'nagad' => 'Nagad Online Payment',
                                'sslcommerz' => 'SSLCommerz Gateway',
                                'manual' => 'Manual Payment / Transfer',
                                default => strtoupper($method),
                            };
                            $methodPct = $metrics['totalOrders'] > 0 ? round(($data['count'] / $metrics['totalOrders']) * 100, 1) : 0;
                        @endphp
                        <div class="py-1.5 flex items-center justify-between text-[11px]">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full {{ $method === 'cod' ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                                <span class="font-semibold text-slate-800">{{ $methodName }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-ink">{{ Money::format($data['total']) }}</span>
                                <span class="text-[10px] text-muted ml-1">({{ $data['count'] }} / {{ $methodPct }}%)</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-xs text-muted">
                    No payment records found.
                </div>
            @endif
        </div>
    </div>

    {{-- Two Performance Tables: Top Products & Low Stock Alert --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2.5 sm:gap-3">
        {{-- Top Selling Products --}}
        <div class="admin-card p-3 sm:p-3.5 bg-white border border-ink/10 shadow-xs rounded-xl">
            <h3 class="text-xs sm:text-sm font-bold text-ink border-b border-ink/10 pb-1.5 mb-2 flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                    <span>🏆</span>
                    <span>Top 5 Best-Selling Products</span>
                </span>
                <span class="text-[11px] text-muted font-normal">By Quantity Sold</span>
            </h3>

            @if(count($metrics['topProducts']) > 0)
                <div class="divide-y divide-ink/5">
                    @foreach($metrics['topProducts'] as $index => $prod)
                        <div class="py-1.5 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2 min-w-0 pr-2">
                                <span class="flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[9px] font-bold text-slate-700">
                                    {{ $index + 1 }}
                                </span>
                                <span class="truncate font-semibold text-slate-800 text-[11px]" title="{{ $prod->product_name }}">
                                    {{ $prod->product_name }}
                                </span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="rounded bg-emerald-50 px-1.5 py-0.5 font-bold text-emerald-700 text-[10px]">
                                    {{ $prod->total_qty }} sold
                                </span>
                                <span class="text-[11px] text-muted ml-1 font-medium">
                                    {{ Money::format($prod->total_revenue) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-xs text-muted">
                    No product sales recorded in this period.
                </div>
            @endif
        </div>

        {{-- Low Stock Inventory Alert --}}
        <div class="admin-card p-3 sm:p-3.5 bg-white border border-ink/10 shadow-xs rounded-xl">
            <h3 class="text-xs sm:text-sm font-bold text-ink border-b border-ink/10 pb-1.5 mb-2 flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                    <span>⚠️</span>
                    <span>Low Stock Alert</span>
                </span>
                <span class="text-[10px] text-rose-600 font-bold">&le; 5 units left</span>
            </h3>

            @if(count($metrics['lowStockProducts']) > 0)
                <div class="divide-y divide-ink/5">
                    @foreach($metrics['lowStockProducts'] as $item)
                        <div class="py-1.5 flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <p class="truncate font-semibold text-slate-800 text-[11px]" title="{{ $item->name }}">{{ $item->name }}</p>
                                <p class="text-[10px] text-muted">SKU: {{ $item->sku ?: 'N/A' }} • Price: {{ Money::format($item->price) }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="rounded-full px-2 py-0.5 font-bold text-[10px] {{ $item->stock <= 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $item->stock }} in stock
                                </span>
                                <a
                                    href="{{ route('admin.products.edit', $item->id) }}"
                                    class="text-indigo-600 hover:text-indigo-800 font-bold hover:underline text-[11px]"
                                    title="Edit stock"
                                >
                                    Restock &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-xs text-emerald-700 font-medium">
                    All products have healthy inventory levels. No shortages!
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ✨ Gemini AI Business Intelligence & Strategic Digest Modal Popup --}}
<div
    id="gemini-report-modal"
    class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-6 overflow-y-auto"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="gemini-report-modal-title"
>
    <div class="relative w-full max-w-3xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh] my-auto">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between border-b border-indigo-900/30 bg-gradient-to-r from-indigo-700 via-indigo-800 to-purple-800 px-5 py-3 text-white shrink-0">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-yellow-300 ring-1 ring-white/30">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </span>
                <div>
                    <h3 id="gemini-report-modal-title" class="text-sm font-bold leading-tight">
                        Gemini AI Business Intelligence & Strategic Digest
                    </h3>
                    <p class="text-[11px] text-indigo-200 font-medium">
                        {{ $rangeLabel }} • Executive Briefing & Strategic Action Plan
                    </p>
                </div>
            </div>
            <button
                type="button"
                onclick="window.closeReportAiModal()"
                class="rounded-lg p-1.5 text-white/80 hover:bg-white/10 hover:text-white transition cursor-pointer"
                aria-label="Close modal"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Modal Sub-header Metrics Strip & Language Toggle --}}
        <div class="bg-indigo-50/60 border-b border-indigo-100 px-4 py-2 flex flex-wrap items-center justify-between gap-2 text-xs shrink-0">
            <div class="flex items-center gap-3 text-slate-700">
                <span><strong>Sales:</strong> {{ Money::format($metrics['grossSales']) }}</span>
                <span>•</span>
                <span><strong>Orders:</strong> {{ number_format($metrics['totalOrders']) }}</span>
                <span>•</span>
                <span><strong>Delivered:</strong> {{ $metrics['deliveryRate'] }}%</span>
                <span>•</span>
                <span><strong>Return:</strong> {{ $metrics['returnRate'] }}%</span>
            </div>

            <div class="flex items-center gap-2">
                {{-- Language Switcher Toggle --}}
                <div class="flex items-center gap-0.5 rounded-lg bg-slate-200/80 p-0.5 text-[11px] font-bold">
                    <button
                        type="button"
                        id="lang-btn-en"
                        onclick="window.setReportAiLanguage('en')"
                        class="px-2 py-0.5 rounded-md bg-white text-indigo-900 shadow-xs transition cursor-pointer"
                        title="English Report"
                    >
                        English
                    </button>
                    <button
                        type="button"
                        id="lang-btn-bn"
                        onclick="window.setReportAiLanguage('bn')"
                        class="px-2 py-0.5 rounded-md text-slate-600 hover:text-slate-900 transition cursor-pointer"
                        title="Bengali Report"
                    >
                        Bengali
                    </button>
                </div>

                <button
                    type="button"
                    id="btn-modal-generate-ai"
                    onclick="window.executeReportAiInsight()"
                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-bold text-white shadow-xs transition active:scale-95 cursor-pointer hover:opacity-95"
                    style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff !important;"
                >
                    <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span id="btn-modal-generate-text">✨ Generate AI Report</span>
                </button>

                <button
                    type="button"
                    id="btn-copy-ai-insight"
                    onclick="window.copyReportAiInsight()"
                    class="hidden rounded-md border border-indigo-200 bg-white px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 transition cursor-pointer"
                    title="Copy briefing to clipboard"
                >
                    📋 Copy Text
                </button>
            </div>
        </div>

        {{-- Modal Content Body --}}
        <div id="ai-modal-scroll-area" class="p-4 sm:p-5 flex-1 overflow-y-auto min-h-[300px] space-y-4">
            {{-- Initial Empty Placeholder --}}
            <div id="ai-insight-placeholder" class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-3 ring-8 ring-indigo-50/50">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <h4 class="text-xs font-bold text-slate-700 mb-1">Ready for AI Executive Analysis</h4>
                <p class="text-[11px] text-slate-500 max-w-sm leading-relaxed mb-3">
                    Click <strong>"✨ Generate AI Report"</strong> or ask any question below to analyze your store performance.
                </p>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        onclick="window.setReportAiLanguage('en'); window.executeReportAiInsight('en');"
                        class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold text-white shadow-xs transition active:scale-95 cursor-pointer"
                        style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);"
                    >
                        ✨ English Report
                    </button>
                    <button
                        type="button"
                        onclick="window.setReportAiLanguage('bn'); window.executeReportAiInsight('bn');"
                        class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold text-white shadow-xs transition active:scale-95 cursor-pointer"
                        style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);"
                    >
                        ✨ Bengali Report
                    </button>
                </div>
            </div>

            {{-- Loading State Spinner --}}
            <div id="ai-insight-loading" class="hidden h-full flex-col items-center justify-center text-center p-8">
                <div class="animate-spin rounded-full h-10 w-10 border-3 border-indigo-600 border-t-transparent mb-3"></div>
                <h4 class="text-xs font-bold text-indigo-900 mb-1">Gemini AI is analyzing store data...</h4>
                <p class="text-[11px] text-slate-500 max-w-xs leading-relaxed">
                    Reviewing gross sales, return rates, delivery logistics, and inventory levels to craft actionable growth strategies. Please wait a moment.
                </p>
            </div>

            {{-- Result Output Box --}}
            <div id="ai-insight-content" class="hidden text-slate-800 leading-relaxed text-xs sm:text-sm">
                <div id="ai-insight-body" class="prose prose-sm max-w-none text-slate-800 space-y-3"></div>
            </div>
        </div>

        {{-- Ask AI Interactive Chat Bar (Talk to AI in Bangla or English) --}}
        <div class="border-t border-slate-200 bg-slate-50/80 p-2.5 sm:p-3 shrink-0 space-y-2">
            {{-- Quick Suggestion Prompt Chips --}}
            <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                <span class="text-slate-500 font-bold text-[10px] uppercase tracking-wide">Ask AI:</span>
                <button type="button" onclick="window.askReportAiQuestion('Give me a comprehensive executive business performance report for this period')" class="rounded-md bg-white px-2 py-0.5 text-indigo-700 border border-indigo-200 hover:bg-indigo-50 transition cursor-pointer font-medium">✨ Full Business Report</button>
                <button type="button" onclick="window.askReportAiQuestion('What are 3 actionable ways to decrease our courier return rate?')" class="rounded-md bg-white px-2 py-0.5 text-indigo-700 border border-indigo-200 hover:bg-indigo-50 transition cursor-pointer font-medium">📉 Lower Returns</button>
                <button type="button" onclick="window.askReportAiQuestion('Which products need immediate restocking and why?')" class="rounded-md bg-white px-2 py-0.5 text-indigo-700 border border-indigo-200 hover:bg-indigo-50 transition cursor-pointer font-medium">📦 Restock Guide</button>
                <button type="button" onclick="window.askReportAiQuestion('What are the top 3 actionable strategies to boost profit this week?')" class="rounded-md bg-white px-2 py-0.5 text-indigo-700 border border-indigo-200 hover:bg-indigo-50 transition cursor-pointer font-medium">💡 Profit Growth</button>
            </div>

            {{-- Input box with Send Button --}}
            <form onsubmit="window.handleReportAiFormSubmit(event)" class="flex items-center gap-2">
                <input
                    type="text"
                    id="report-ai-question-input"
                    placeholder="Ask AI anything about this report (e.g. top selling trends, profit drivers)..."
                    class="field !py-1.5 !px-3 !text-xs w-full bg-white shadow-xs focus:bg-white border-slate-300"
                    autocomplete="off"
                />
                <button
                    type="submit"
                    id="btn-report-ai-ask"
                    class="inline-flex items-center gap-1 rounded-lg px-3.5 py-1.5 text-xs font-bold text-white shadow-xs transition active:scale-95 cursor-pointer hover:opacity-95 shrink-0"
                    style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    <span>Send</span>
                </button>
            </form>
        </div>

        {{-- Modal Footer --}}
        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-100/70 px-4 py-2 shrink-0 text-xs">
            <span class="text-[11px] text-slate-500">
                Powered by Google Gemini AI • Ask in English, Bangla or Banglish
            </span>
            <button
                type="button"
                onclick="window.closeReportAiModal()"
                class="rounded-lg px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
            >
                Close
            </button>
        </div>
    </div>
</div>

{{-- Gemini AI Modal Controller Script --}}
<script>
(function() {
    window.rawAiInsightText = '';
    window.currentReportAiLang = 'en';

    window.openReportAiModal = function() {
        var modal = document.getElementById('gemini-report-modal');
        if (!modal) return;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        modal.style.display = 'flex';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // If not generated yet, auto-trigger generation on first open
        if (!window.rawAiInsightText) {
            window.executeReportAiInsight();
        }
    };

    window.closeReportAiModal = function() {
        var modal = document.getElementById('gemini-report-modal');
        if (!modal) return;
        modal.style.display = 'none';
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.setReportAiLanguage = function(lang) {
        window.currentReportAiLang = lang;

        var btnEn = document.getElementById('lang-btn-en');
        var btnBn = document.getElementById('lang-btn-bn');

        if (lang === 'bn') {
            if (btnBn) btnBn.className = 'px-2 py-0.5 rounded-md bg-white text-indigo-900 shadow-xs transition cursor-pointer';
            if (btnEn) btnEn.className = 'px-2 py-0.5 rounded-md text-slate-600 hover:text-slate-900 transition cursor-pointer';
        } else {
            if (btnEn) btnEn.className = 'px-2 py-0.5 rounded-md bg-white text-indigo-900 shadow-xs transition cursor-pointer';
            if (btnBn) btnBn.className = 'px-2 py-0.5 rounded-md text-slate-600 hover:text-slate-900 transition cursor-pointer';
        }

        window.executeReportAiInsight(lang);
    };

    function renderMarkdown(text) {
        if (!text) return '';
        return text
            .replace(/^### (.*$)/gim, '<h4 class="text-sm font-bold text-indigo-950 mt-3 mb-1.5 border-b border-indigo-100 pb-1">$1</h4>')
            .replace(/^## (.*$)/gim, '<h3 class="text-base font-bold text-indigo-950 mt-4 mb-2">$1</h3>')
            .replace(/\*\*(.*?)\*\*/gim, '<strong class="font-bold text-slate-900">$1</strong>')
            .replace(/^\s*-\s+(.*$)/gim, '<li class="ml-4 list-disc text-slate-700 leading-relaxed">$1</li>')
            .replace(/\n\n/gim, '<div class="h-2"></div>')
            .replace(/\n/gim, '<br>');
    }

    window.executeReportAiInsight = async function(lang, question = null) {
        const selectedLang = lang || window.currentReportAiLang || 'en';
        const btn = document.getElementById('btn-modal-generate-ai');
        const btnText = document.getElementById('btn-modal-generate-text');
        const placeholder = document.getElementById('ai-insight-placeholder');
        const loadingBox = document.getElementById('ai-insight-loading');
        const contentBox = document.getElementById('ai-insight-content');
        const bodyBox = document.getElementById('ai-insight-body');
        const copyBtn = document.getElementById('btn-copy-ai-insight');
        const askBtn = document.getElementById('btn-report-ai-ask');
        const scrollArea = document.getElementById('ai-modal-scroll-area');

        const isQuestion = Boolean(question && question.trim());

        if (isQuestion) {
            if (askBtn) askBtn.disabled = true;

            // Append User Question bubble
            if (placeholder) placeholder.classList.add('hidden');
            if (contentBox) contentBox.classList.remove('hidden');

            const userQuestionHtml = `
                <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-3 my-2 text-xs">
                    <span class="font-bold text-indigo-900 flex items-center gap-1.5 mb-1">
                        <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        Your Question:
                    </span>
                    <p class="text-slate-800 font-medium">${question}</p>
                </div>
                <div id="ai-typing-temp" class="text-xs text-indigo-700 italic flex items-center gap-1.5 py-1">
                    <span class="animate-pulse">✨ Gemini AI is thinking...</span>
                </div>
            `;
            if (bodyBox) {
                bodyBox.insertAdjacentHTML('beforeend', userQuestionHtml);
            }
            if (scrollArea) scrollArea.scrollTop = scrollArea.scrollHeight;

        } else {
            // Full report regeneration
            if (btn) btn.disabled = true;
            if (btnText) btnText.textContent = 'Analyzing...';
            if (placeholder) placeholder.classList.add('hidden');
            if (contentBox) contentBox.classList.add('hidden');
            if (loadingBox) {
                loadingBox.classList.remove('hidden');
                loadingBox.classList.add('flex');
            }
        }

        try {
            const response = await fetch('{{ route('admin.reports.ai-insight') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    range: '{{ $activeRange }}',
                    from: '{{ $startDate }}',
                    to: '{{ $endDate }}',
                    language: selectedLang,
                    question: isQuestion ? question : null
                })
            });

            const res = await response.json();

            if (!response.ok || !res.ok) {
                alert(res.message || 'Unable to generate AI report. Please check Gemini settings.');
                const tempTyping = document.getElementById('ai-typing-temp');
                if (tempTyping) tempTyping.remove();
                if (loadingBox) {
                    loadingBox.classList.add('hidden');
                    loadingBox.classList.remove('flex');
                }
                if (!isQuestion && placeholder) placeholder.classList.remove('hidden');
                return;
            }

            const insightText = res.insight || '';
            const formatted = renderMarkdown(insightText);

            if (isQuestion) {
                const tempTyping = document.getElementById('ai-typing-temp');
                if (tempTyping) tempTyping.remove();

                const answerHtml = `
                    <div class="rounded-xl bg-white border border-slate-200 p-3.5 my-2 shadow-xs text-xs sm:text-sm">
                        <span class="font-bold text-slate-900 flex items-center gap-1.5 mb-2 pb-1.5 border-b border-slate-100">
                            <span class="text-indigo-600">✨ Gemini AI Response:</span>
                        </span>
                        <div class="prose prose-sm max-w-none text-slate-800 space-y-1.5">${formatted}</div>
                    </div>
                `;
                if (bodyBox) {
                    bodyBox.insertAdjacentHTML('beforeend', answerHtml);
                }
            } else {
                window.rawAiInsightText = insightText;

                if (loadingBox) {
                    loadingBox.classList.add('hidden');
                    loadingBox.classList.remove('flex');
                }
                if (contentBox) contentBox.classList.remove('hidden');
                if (copyBtn) copyBtn.classList.remove('hidden');

                if (bodyBox) {
                    bodyBox.innerHTML = formatted;
                }

                if (btnText) {
                    btnText.textContent = '🔄 Regenerate';
                }
            }

            if (scrollArea) {
                setTimeout(() => { scrollArea.scrollTop = scrollArea.scrollHeight; }, 50);
            }

        } catch (err) {
            console.error('AI Insight Error:', err);
            alert('Network error while requesting AI report.');
            const tempTyping = document.getElementById('ai-typing-temp');
            if (tempTyping) tempTyping.remove();
            if (loadingBox) {
                loadingBox.classList.add('hidden');
                loadingBox.classList.remove('flex');
            }
            if (!isQuestion && placeholder) placeholder.classList.remove('hidden');
        } finally {
            if (btn) btn.disabled = false;
            if (askBtn) askBtn.disabled = false;
            if (btnText && btnText.textContent === 'Analyzing...') {
                btnText.textContent = '✨ Generate AI Report';
            }
        }
    };

    window.askReportAiQuestion = function(questionText) {
        var input = document.getElementById('report-ai-question-input');
        if (input) input.value = questionText;
        window.executeReportAiInsight(window.currentReportAiLang, questionText);
        if (input) input.value = '';
    };

    window.handleReportAiFormSubmit = function(e) {
        if (e && e.preventDefault) e.preventDefault();
        var input = document.getElementById('report-ai-question-input');
        if (!input) return;
        var q = input.value.trim();
        if (!q) return;

        // Auto-detect Bengali characters in question to match reply language
        var hasBangla = /[\u0980-\u09FF]/.test(q);
        var targetLang = hasBangla ? 'bn' : window.currentReportAiLang;

        window.executeReportAiInsight(targetLang, q);
        input.value = '';
    };

    window.copyReportAiInsight = function() {
        if (!window.rawAiInsightText) return;
        navigator.clipboard.writeText(window.rawAiInsightText).then(function() {
            var copyBtn = document.getElementById('btn-copy-ai-insight');
            if (copyBtn) {
                var oldText = copyBtn.textContent;
                copyBtn.textContent = '✅ Copied!';
                setTimeout(function() {
                    copyBtn.textContent = oldText;
                }, 2000);
            }
        });
    };

    function initReportModal() {
        var modal = document.getElementById('gemini-report-modal');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) window.closeReportAiModal();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReportModal);
    } else {
        initReportModal();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var modal = document.getElementById('gemini-report-modal');
            if (modal && modal.style.display !== 'none' && !modal.classList.contains('hidden')) {
                window.closeReportAiModal();
            }
        }
    });
})();
</script>
@endsection
