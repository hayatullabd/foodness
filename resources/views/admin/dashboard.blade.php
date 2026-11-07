@extends('layouts.admin')

@php
    use App\Support\Money;

    $maxRevenue = max(1, (float) collect($revenueSeries)->max('total'));
    $chartTotal = (float) $chartTotal;

    // Y-Axis scale calculation
    $stepFactor = $maxRevenue > 10000 ? 5000 : ($maxRevenue > 2000 ? 1000 : 500);
    $yCeil = max(500, ceil($maxRevenue / $stepFactor) * $stepFactor);
    $yTicks = [
        ['val' => $yCeil, 'label' => '৳' . ($yCeil >= 1000 ? ($yCeil / 1000) . 'k' : $yCeil)],
        ['val' => $yCeil * 0.75, 'label' => '৳' . ($yCeil * 0.75 >= 1000 ? ($yCeil * 0.75 / 1000) . 'k' : round($yCeil * 0.75))],
        ['val' => $yCeil * 0.50, 'label' => '৳' . ($yCeil * 0.50 >= 1000 ? ($yCeil * 0.50 / 1000) . 'k' : round($yCeil * 0.50))],
        ['val' => $yCeil * 0.25, 'label' => '৳' . round($yCeil * 0.25)],
        ['val' => 0, 'label' => '৳0'],
    ];

    // Pipeline status counts
    $orderCount = (int) $orderCount;
    $pendingOrderCount = (int) $pendingOrderCount;
    $inProgressOrderCount = (int) $inProgressOrderCount;
    $deliveredOrderCount = (int) $deliveredOrderCount;

    $deliveredPercent = $orderCount > 0 ? round(($deliveredOrderCount / $orderCount) * 100) : 0;
    $processingPercent = $orderCount > 0 ? round(($inProgressOrderCount / $orderCount) * 100) : 0;
    $pendingPercent = $orderCount > 0 ? max(0, 100 - $deliveredPercent - $processingPercent) : 0;

    $circumference = 2 * M_PI * 40; // ~251.32
    $donutSegments = [];
    $donutOffset = 0;

    $pipeline = [
        ['label' => 'Delivered', 'count' => $deliveredOrderCount, 'percent' => $deliveredPercent, 'color' => '#10b981'],
        ['label' => 'Processing', 'count' => $inProgressOrderCount, 'percent' => $processingPercent, 'color' => '#3b82f6'],
        ['label' => 'Pending', 'count' => $pendingOrderCount, 'percent' => $pendingPercent, 'color' => '#ea580c'],
    ];

    foreach ($pipeline as $p) {
        $length = $circumference * ($p['percent'] / 100);
        $donutSegments[] = [
            ...$p,
            'dash' => $length,
            'gap' => max(0, $circumference - $length),
            'offset' => -$donutOffset,
        ];
        $donutOffset += $length;
    }

    // Inventory Health
    $lowStockCount = (int) ($lowStockCount ?? $lowStockProducts->count());
    $inStockCount = max(0, $productCount - $lowStockCount);
    $stockPercentage = $productCount > 0 ? min(100, round(($inStockCount / $productCount) * 100)) : 100;
    $isStockHealthy = $lowStockCount === 0;
    $chartTrend = (float) ($chartTrend ?? $revenueTrend);
    $chartTrendLabel = $chartTrendLabel ?? 'vs prior period';

    // SVG Bar Chart Dimensions & Geometry
    $svgWidth = 660;
    $svgHeight = 190;
    $chartTop = 22;
    $chartBottom = 160;
    $chartHeight = $chartBottom - $chartTop; // 138px
    $chartLeft = 52;
    $chartRight = 645;
    $plotWidth = $chartRight - $chartLeft;

    $pointCount = max(1, count($revenueSeries));
    $colWidth = $plotWidth / $pointCount;
    $barWidth = max(7, min(20, $colWidth * 0.62));

    $highestVal = collect($revenueSeries)->max('total');
@endphp

@section('title', 'Dashboard')

@section('content')
<div class="space-y-4 sm:space-y-6">

    {{-- Header + KPI strip --}}
    <div class="flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $greeting }}</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Here's what's happening in your store today.</p>
            </div>
            @if ($pendingOrderCount > 0)
                <a
                    href="{{ route('admin.orders.index', ['status' => 'pending']) }}"
                    class="inline-flex items-center gap-2 self-start rounded-full border border-amber-200/70 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    {{ number_format($pendingOrderCount) }} pending order{{ $pendingOrderCount === 1 ? '' : 's' }}
                </a>
            @endif
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Today’s revenue</p>
                <p class="mt-1.5 text-lg sm:text-xl font-extrabold text-slate-900 tabular-nums">{{ Money::format($todayRevenue) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($todayOrderCount) }} order{{ $todayOrderCount === 1 ? '' : 's' }} today</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">This month</p>
                <p class="mt-1.5 text-lg sm:text-xl font-extrabold text-slate-900 tabular-nums">{{ Money::format($monthRevenue) }}</p>
                <p class="mt-1 text-xs {{ $revenueTrend >= 0 ? 'text-emerald-600' : 'text-red-500' }} font-semibold">
                    {{ $revenueTrend >= 0 ? '+' : '' }}{{ number_format($revenueTrend, 1) }}% vs last month
                </p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Orders</p>
                <p class="mt-1.5 text-lg sm:text-xl font-extrabold text-slate-900 tabular-nums">{{ number_format($monthOrderCount) }}</p>
                <p class="mt-1 text-xs {{ $ordersTrend >= 0 ? 'text-emerald-600' : 'text-red-500' }} font-semibold">
                    {{ $ordersTrend >= 0 ? '+' : '' }}{{ number_format($ordersTrend, 1) }}% vs last month
                </p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Customers</p>
                <p class="mt-1.5 text-lg sm:text-xl font-extrabold text-slate-900 tabular-nums">{{ number_format($customerCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ number_format($newCustomersThisMonth) }} new this month
                    @if ($customersTrend != 0)
                        <span class="{{ $customersTrend >= 0 ? 'text-emerald-600' : 'text-red-500' }} font-semibold">
                            ({{ $customersTrend >= 0 ? '+' : '' }}{{ number_format($customersTrend, 1) }}%)
                        </span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- ROW 1: Revenue Overview + Order Status --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch">

        {{-- 1. Revenue Overview Card --}}
        <section class="lg:col-span-8 rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-[0_1px_3px_rgba(0,0,0,0.04)] flex flex-col justify-between">
            <div>
                {{-- Card Header & Time Range Tabs --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Revenue overview</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Track your store performance over time</p>
                    </div>

                    {{-- Period Segmentation Pills --}}
                    <div
                        class="inline-flex items-center rounded-xl bg-slate-100 p-1 border border-slate-200/70 text-xs font-medium self-start sm:self-auto select-none"
                        style="background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.25rem;"
                    >
                        <a
                            href="{{ route('admin.dashboard', ['range' => '7']) }}"
                            class="rounded-lg px-3 py-1 transition-all {{ $chartRange === '7' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-900 font-medium' }}"
                            style="{{ $chartRange === '7' ? 'background: #ffffff; color: #0f172a; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.06); border-radius: 0.5rem; padding: 0.25rem 0.75rem;' : 'color: #64748b; padding: 0.25rem 0.75rem; border-radius: 0.5rem;' }}"
                        >7 days</a>
                        <a
                            href="{{ route('admin.dashboard', ['range' => '30']) }}"
                            class="rounded-lg px-3 py-1 transition-all {{ $chartRange === '30' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-900 font-medium' }}"
                            style="{{ $chartRange === '30' ? 'background: #ffffff; color: #0f172a; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.06); border-radius: 0.5rem; padding: 0.25rem 0.75rem;' : 'color: #64748b; padding: 0.25rem 0.75rem; border-radius: 0.5rem;' }}"
                        >30 days</a>
                        <a
                            href="{{ route('admin.dashboard', ['range' => 'year']) }}"
                            class="rounded-lg px-3 py-1 transition-all {{ $chartRange === 'year' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-900 font-medium' }}"
                            style="{{ $chartRange === 'year' ? 'background: #ffffff; color: #0f172a; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.06); border-radius: 0.5rem; padding: 0.25rem 0.75rem;' : 'color: #64748b; padding: 0.25rem 0.75rem; border-radius: 0.5rem;' }}"
                        >Year</a>
                    </div>
                </div>

                {{-- Metric Total & Trend (aligned to selected chart range) --}}
                <div class="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 font-sans">
                        {{ Money::format($chartTotal) }}
                    </span>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $chartTrend >= 0 ? 'text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full' : 'text-red-500 bg-red-50 px-2 py-0.5 rounded-full' }}">
                        @if ($chartTrend >= 0)
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" /></svg>
                        @else
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5l15 15m0 0V8.25m0 11.25H8.25" /></svg>
                        @endif
                        {{ number_format(abs($chartTrend), 1) }}%
                    </span>
                    <span class="text-[11px] text-slate-400 font-medium">{{ $chartTrendLabel }}</span>
                </div>
            </div>

            {{-- Crisp SVG Responsive Bar Chart --}}
            <div class="mt-4 pt-1 w-full overflow-hidden">
                <svg
                    viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}"
                    class="w-full h-auto max-h-56 select-none"
                    preserveAspectRatio="xMidYMid meet"
                >
                    {{-- Y-Axis Reference Guide Lines --}}
                    @foreach ($yTicks as $i => $tick)
                        @php
                            $lineY = $chartTop + ($i * ($chartHeight / 4));
                        @endphp
                        {{-- Y label --}}
                        <text
                            x="45"
                            y="{{ $lineY + 3 }}"
                            font-size="10"
                            fill="#94a3b8"
                            font-family="sans-serif"
                            text-anchor="end"
                        >{{ $tick['label'] }}</text>

                        {{-- Horizontal line --}}
                        <line
                            x1="{{ $chartLeft }}"
                            y1="{{ $lineY }}"
                            x2="{{ $chartRight }}"
                            y2="{{ $lineY }}"
                            stroke="{{ $i === 4 ? '#e2e8f0' : '#f1f5f9' }}"
                            stroke-dasharray="{{ $i === 4 ? 'none' : '3,3' }}"
                            stroke-width="1"
                        />
                    @endforeach

                    {{-- Vertical Bars --}}
                    @php
                        $lastIdx = count($revenueSeries) - 1;
                    @endphp

                    @foreach ($revenueSeries as $idx => $point)
                        @php
                            $barX = $chartLeft + ($idx * $colWidth) + (($colWidth - $barWidth) / 2);
                            $val = (float) $point['total'];
                            
                            // If revenue is 0, give minimal 4px dot/bar at base for visual consistency
                            $ratio = $yCeil > 0 ? ($val / $yCeil) : 0;
                            $barH = $val > 0 ? max(8, round($ratio * $chartHeight)) : 4;
                            $barY = $chartBottom - $barH;

                            $isPeak = ($val > 0 && ($val === $highestVal || $idx === $lastIdx));
                            $fillColor = $isPeak ? '#059669' : ($val > 0 ? '#10b981' : '#cbd5e1');
                            $fillOpacity = $val > 0 ? ($isPeak ? '1' : '0.85') : '0.45';
                        @endphp

                        {{-- Bar Rect --}}
                        <rect
                            x="{{ $barX }}"
                            y="{{ $barY }}"
                            width="{{ $barWidth }}"
                            height="{{ $barH }}"
                            rx="3"
                            ry="3"
                            fill="{{ $fillColor }}"
                            fill-opacity="{{ $fillOpacity }}"
                            class="transition-all duration-200 cursor-pointer"
                        >
                            <title>{{ $point['label'] }}: {{ Money::format($val) }} ({{ $point['count'] }} orders)</title>
                        </rect>

                        {{-- Tooltip Badge above active/peak bar --}}
                        @if ($isPeak)
                            @php
                                $tooltipW = 58;
                                $tooltipH = 20;
                                $tooltipX = $barX + ($barWidth / 2) - ($tooltipW / 2);
                                $tooltipY = max(2, $barY - $tooltipH - 6);
                            @endphp
                            <g>
                                <rect
                                    x="{{ $tooltipX }}"
                                    y="{{ $tooltipY }}"
                                    width="{{ $tooltipW }}"
                                    height="{{ $tooltipH }}"
                                    rx="4"
                                    fill="#0f172a"
                                />
                                <polygon
                                    points="{{ $barX + ($barWidth / 2) - 4 }},{{ $tooltipY + $tooltipH }} {{ $barX + ($barWidth / 2) + 4 }},{{ $tooltipY + $tooltipH }} {{ $barX + ($barWidth / 2) }},{{ $tooltipY + $tooltipH + 4 }}"
                                    fill="#0f172a"
                                />
                                <text
                                    x="{{ $barX + ($barWidth / 2) }}"
                                    y="{{ $tooltipY + 14 }}"
                                    fill="#ffffff"
                                    font-size="10.5"
                                    font-weight="bold"
                                    font-family="sans-serif"
                                    text-anchor="middle"
                                >{{ Money::format($val) }}</text>
                            </g>
                        @endif
                    @endforeach

                    {{-- X-Axis Date Labels --}}
                    @if (count($revenueSeries) <= 7)
                        @foreach ($revenueSeries as $idx => $p)
                            @php
                                $labelX = $chartLeft + ($idx * $colWidth) + ($colWidth / 2);
                            @endphp
                            <text
                                x="{{ $labelX }}"
                                y="180"
                                font-size="10"
                                fill="#94a3b8"
                                font-family="sans-serif"
                                text-anchor="middle"
                            >{{ $p['label'] }}</text>
                        @endforeach
                    @else
                        {{-- Sampled labels across the axis --}}
                        @php
                            $sampleIndices = [
                                0,
                                intval(count($revenueSeries) * 0.33),
                                intval(count($revenueSeries) * 0.66),
                                count($revenueSeries) - 1,
                            ];
                        @endphp
                        @foreach ($sampleIndices as $sIdx)
                            @if (isset($revenueSeries[$sIdx]))
                                @php
                                    $labelX = $chartLeft + ($sIdx * $colWidth) + ($colWidth / 2);
                                @endphp
                                <text
                                    x="{{ $labelX }}"
                                    y="180"
                                    font-size="10"
                                    fill="#94a3b8"
                                    font-family="sans-serif"
                                    text-anchor="middle"
                                >{{ $revenueSeries[$sIdx]['label'] }}</text>
                            @endif
                        @endforeach
                    @endif
                </svg>
            </div>
        </section>

        {{-- 2. Order Status Card --}}
        <section class="lg:col-span-4 rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-[0_1px_3px_rgba(0,0,0,0.04)] flex flex-col justify-between">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Order status</h2>
                <p class="text-xs text-slate-400 mt-0.5">Distribution across your pipeline</p>

                {{-- Sleek Circular Donut Chart --}}
                <div class="relative h-36 w-36 sm:h-40 sm:w-40 mx-auto my-4 sm:my-5">
                    <svg viewBox="0 0 100 100" class="-rotate-90 h-full w-full">
                        {{-- Track Circle --}}
                        <circle cx="50" cy="50" r="40" fill="none" stroke="#f1f5f9" stroke-width="9" />
                        
                        {{-- Colored Arc Segments --}}
                        @foreach ($donutSegments as $segment)
                            @if ($segment['dash'] > 0)
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="40"
                                    fill="none"
                                    stroke="{{ $segment['color'] }}"
                                    stroke-width="9"
                                    stroke-dasharray="{{ $segment['dash'] }} {{ $segment['gap'] }}"
                                    stroke-dashoffset="{{ $segment['offset'] }}"
                                    stroke-linecap="butt"
                                    class="transition-all duration-500"
                                />
                            @endif
                        @endforeach
                    </svg>

                    {{-- Donut Inner Text --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-sans tracking-tight">
                            {{ number_format($orderCount) }}
                        </span>
                        <span class="text-[11px] text-slate-400 font-medium">Total orders</span>
                    </div>
                </div>
            </div>

            {{-- Legend List --}}
            <div class="space-y-2 pt-3 border-t border-slate-100">
                @foreach ($pipeline as $item)
                    <div class="flex items-center justify-between text-xs py-1 px-1.5 rounded-lg hover:bg-slate-50 transition">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full shrink-0" style="background-color: {{ $item['color'] }};"></span>
                            <span class="text-slate-700 font-medium">{{ $item['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-3 tabular-nums font-semibold">
                            <span class="text-slate-900 w-6 text-right">{{ number_format($item['count']) }}</span>
                            <span class="text-slate-400 w-10 text-right">{{ number_format($item['percent'], 0) }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- ======================================================== --}}
    {{-- ROW 2: Recent Orders (8 cols) + Inventory Health (4 cols) --}}
    {{-- ======================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch">

        {{-- 3. Recent Orders Card --}}
        <section class="lg:col-span-8 rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-[0_1px_3px_rgba(0,0,0,0.04)] flex flex-col justify-between">
            <div>
                {{-- Card Header & View All --}}
                <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Recent orders</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Latest transactions from your store</p>
                    </div>
                    <a
                        href="{{ route('admin.orders.index') }}"
                        class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:underline transition"
                    >View all</a>
                </div>

                {{-- Responsive Orders Table --}}
                <div class="overflow-x-auto no-scrollbar -mx-5 sm:-mx-6 px-5 sm:px-6">
                    <table class="w-full text-left border-collapse min-w-[560px]">
                        <thead>
                            <tr class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                <th class="py-3 pr-4">Order ID</th>
                                <th class="py-3 px-4">Customer</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Total</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 pl-4 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse ($recentOrders as $order)
                                @php
                                    $badgeStyle = match ($order->status) {
                                        'pending', 'unpaid' => 'bg-amber-50 text-amber-700 border-amber-200/60',
                                        'processing', 'on_the_way', 'in_courier' => 'bg-blue-50 text-blue-700 border-blue-200/60',
                                        'delivered', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
                                        'cancelled' => 'bg-red-50 text-red-700 border-red-200/60',
                                        default => 'bg-slate-50 text-slate-700 border-slate-200/60',
                                    };
                                    $initials = collect(preg_split('/\s+/', trim((string) $order->customer_name) ?: 'Guest'))
                                        ->filter()
                                        ->take(2)
                                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                        ->implode('');
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    {{-- Order ID --}}
                                    <td class="py-3.5 pr-4 whitespace-nowrap">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-emerald-700 hover:underline">
                                            #{{ $order->invoice_no }}
                                        </a>
                                    </td>

                                    {{-- Customer --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-600 border border-slate-200/70">
                                                {{ $initials ?: 'G' }}
                                            </span>
                                            <span class="font-medium text-slate-800 max-w-[10rem] truncate" title="{{ $order->customer_name }}">
                                                {{ $order->customer_name }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Date --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 font-medium">
                                        {{ $order->created_at?->format('d M, h:i A') }}
                                    </td>

                                    {{-- Total --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap font-bold text-slate-900 tabular-nums">
                                        {{ Money::format($order->total) }}
                                    </td>

                                    {{-- Status Pill --}}
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold border {{ $badgeStyle }}">
                                            {{ $order->statusLabel() }}
                                        </span>
                                    </td>

                                    {{-- Action Dots Menu --}}
                                    <td class="py-3.5 pl-4 text-right whitespace-nowrap">
                                        <a
                                            href="{{ route('admin.orders.show', $order) }}"
                                            class="inline-flex items-center justify-center h-7 w-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                                            title="View order"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM18 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                                        No recent transactions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- 4. Inventory Health & Quick Actions --}}
        <section class="lg:col-span-4 rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-[0_1px_3px_rgba(0,0,0,0.04)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Inventory health</h2>
                        <p class="text-xs text-slate-400 mt-0.5">≤ {{ $lowStockThreshold }} units flagged</p>
                    </div>

                    @if ($isStockHealthy)
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200/60">
                            Healthy
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200/60">
                            Attention
                        </span>
                    @endif
                </div>

                <div class="mt-5">
                    <div class="flex items-center justify-between text-xs font-medium">
                        <span class="text-slate-800 font-semibold">Products in stock</span>
                        <span class="text-slate-900 font-bold tabular-nums">{{ $inStockCount }} / {{ $productCount }}</span>
                    </div>

                    <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden my-2.5">
                        <div
                            class="h-full rounded-full {{ $isStockHealthy ? 'bg-emerald-600' : 'bg-amber-500' }} transition-all duration-500"
                            style="width: {{ $stockPercentage }}%;"
                        ></div>
                    </div>

                    <p class="text-xs text-slate-400">
                        @if ($isStockHealthy)
                            All active products have sufficient stock.
                        @else
                            {{ number_format($lowStockCount) }} product{{ $lowStockCount === 1 ? '' : 's' }} need replenishment.
                        @endif
                    </p>
                </div>

                @if ($lowStockProducts->isNotEmpty())
                    <ul class="mt-4 space-y-2">
                        @foreach ($lowStockProducts as $product)
                            <li>
                                <a
                                    href="{{ route('admin.products.edit', $product) }}"
                                    class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 -mx-0.5 hover:bg-slate-50 transition"
                                >
                                    <span class="min-w-0">
                                        <span class="block text-xs font-semibold text-slate-800 truncate">{{ $product->name }}</span>
                                        @if ($product->sku)
                                            <span class="block text-[10px] text-slate-400 truncate">SKU {{ $product->sku }}</span>
                                        @endif
                                    </span>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold tabular-nums border {{ (int) $product->stock <= 0 ? 'bg-red-50 text-red-700 border-red-200/60' : 'bg-amber-50 text-amber-700 border-amber-200/60' }}">
                                        {{ (int) $product->stock }} left
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @if ($lowStockCount > $lowStockProducts->count())
                        <a href="{{ route('admin.products.index') }}" class="mt-2 inline-block text-xs font-semibold text-emerald-700 hover:underline">
                            View all {{ number_format($lowStockCount) }} low-stock items
                        </a>
                    @endif
                @endif
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Quick actions</p>

                <div class="grid grid-cols-2 gap-2.5">
                    <a
                        href="{{ route('admin.orders.index', ['status' => 'pending']) }}"
                        class="flex items-center justify-between gap-2 p-3 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-slate-300 hover:shadow-xs transition text-xs font-medium text-slate-700"
                    >
                        <span class="truncate">Pending</span>
                        @if ($pendingOrderCount > 0)
                            <span class="rounded-full bg-amber-50 text-amber-700 px-2 py-0.5 text-[11px] font-bold border border-amber-200/60 shrink-0">
                                {{ $pendingOrderCount }}
                            </span>
                        @endif
                    </a>
                    <a
                        href="{{ route('admin.customers.index') }}"
                        class="flex items-center p-3 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-slate-300 hover:shadow-xs transition text-xs font-medium text-slate-700"
                    >
                        Customers
                    </a>
                    <a
                        href="{{ route('admin.products.index') }}"
                        class="flex items-center p-3 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-slate-300 hover:shadow-xs transition text-xs font-medium text-slate-700"
                    >
                        Products
                    </a>
                    <a
                        href="{{ route('admin.courier-panel.index') }}"
                        class="flex items-center p-3 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-slate-300 hover:shadow-xs transition text-xs font-medium text-slate-700"
                    >
                        Courier
                    </a>
                </div>
            </div>
        </section>
    </div>

</div>
@endsection
