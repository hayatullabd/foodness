@extends('layouts.admin')

@php
    use App\Support\Money;

    $maxRevenue = max(1, (float) collect($revenueSeries)->max('total'));
    $chartTotal = (float) $chartTotal;

    $stepFactor = $maxRevenue > 10000 ? 5000 : ($maxRevenue > 2000 ? 1000 : 500);
    $yCeil = max(500, ceil($maxRevenue / $stepFactor) * $stepFactor);
    $yTicks = [
        ['val' => $yCeil, 'label' => '৳'.($yCeil >= 1000 ? ($yCeil / 1000).'k' : $yCeil)],
        ['val' => $yCeil * 0.75, 'label' => '৳'.($yCeil * 0.75 >= 1000 ? ($yCeil * 0.75 / 1000).'k' : round($yCeil * 0.75))],
        ['val' => $yCeil * 0.50, 'label' => '৳'.($yCeil * 0.50 >= 1000 ? ($yCeil * 0.50 / 1000).'k' : round($yCeil * 0.50))],
        ['val' => $yCeil * 0.25, 'label' => '৳'.round($yCeil * 0.25)],
        ['val' => 0, 'label' => '৳0'],
    ];

    $orderCount = (int) $orderCount;
    $pendingOrderCount = (int) $pendingOrderCount;
    $inProgressOrderCount = (int) $inProgressOrderCount;
    $deliveredOrderCount = (int) $deliveredOrderCount;
    $incompleteOrderCount = (int) ($incompleteOrderCount ?? 0);

    $deliveredPercent = $orderCount > 0 ? round(($deliveredOrderCount / $orderCount) * 100) : 0;
    $processingPercent = $orderCount > 0 ? round(($inProgressOrderCount / $orderCount) * 100) : 0;
    $pendingPercent = $orderCount > 0 ? max(0, 100 - $deliveredPercent - $processingPercent) : 0;

    $circumference = 2 * M_PI * 40;
    $donutSegments = [];
    $donutOffset = 0;

    $pipeline = [
        ['label' => 'Delivered', 'count' => $deliveredOrderCount, 'percent' => $deliveredPercent, 'color' => '#19a800', 'href' => route('admin.orders.index', ['status' => 'delivered'])],
        ['label' => 'Processing', 'count' => $inProgressOrderCount, 'percent' => $processingPercent, 'color' => '#0284c7', 'href' => route('admin.orders.index', ['status' => 'processing'])],
        ['label' => 'Pending', 'count' => $pendingOrderCount, 'percent' => $pendingPercent, 'color' => '#d97706', 'href' => route('admin.orders.index', ['status' => 'pending'])],
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

    $lowStockCount = (int) ($lowStockCount ?? $lowStockProducts->count());
    $inStockCount = max(0, $productCount - $lowStockCount);
    $stockPercentage = $productCount > 0 ? min(100, round(($inStockCount / $productCount) * 100)) : 100;
    $isStockHealthy = $lowStockCount === 0;
    $chartTrend = (float) ($chartTrend ?? $revenueTrend);
    $chartTrendLabel = $chartTrendLabel ?? 'vs prior period';
    $avgOrderValue = $monthOrderCount > 0 ? ((float) $monthRevenue / $monthOrderCount) : 0;

    $svgWidth = 660;
    $svgHeight = 210;
    $chartTop = 28;
    $chartBottom = 175;
    $chartHeight = $chartBottom - $chartTop;
    $chartLeft = 52;
    $chartRight = 645;
    $plotWidth = $chartRight - $chartLeft;
    $pointCount = max(1, count($revenueSeries));
    $colWidth = $plotWidth / $pointCount;
    $barWidth = max(7, min(22, $colWidth * 0.62));
    $highestVal = collect($revenueSeries)->max('total');

    $attentionItems = [
        [
            'label' => 'Pending orders',
            'count' => $pendingOrderCount,
            'href' => route('admin.orders.index', ['status' => 'pending']),
            'tone' => 'warning',
        ],
        [
            'label' => 'Incomplete carts',
            'count' => $incompleteOrderCount,
            'href' => route('admin.orders.index', ['tab' => 'incomplete']),
            'tone' => 'neutral',
        ],
        [
            'label' => 'In progress',
            'count' => $inProgressOrderCount,
            'href' => route('admin.orders.index', ['status' => 'processing']),
            'tone' => 'info',
        ],
        [
            'label' => 'Low stock',
            'count' => $lowStockCount,
            'href' => route('admin.products.index'),
            'tone' => $lowStockCount > 0 ? 'danger' : 'success',
        ],
    ];
@endphp

@section('title', 'Dashboard')
@section('heading', $greeting)
@section('subheading', 'Store performance overview — today and this month')

@section('actions')
    <a href="{{ route('admin.orders.create') }}" class="admin-btn-secondary">Create order</a>
    <a href="{{ route('admin.products.create') }}" class="admin-btn-primary">Add product</a>
@endsection

@section('content')
<div class="admin-premium-dashboard dash-dense space-y-3 sm:space-y-3.5">

    {{-- Dense KPI strip --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2.5">
        <div class="dash-kpi">
            <div class="dash-kpi-top">
                <p class="dash-kpi-label">Today revenue</p>
                <span class="dash-kpi-icon dash-kpi-icon--sage">Tk</span>
            </div>
            <p class="dash-kpi-value">{{ Money::format($todayRevenue) }}</p>
            <p class="dash-kpi-hint">{{ number_format($todayOrderCount) }} orders today</p>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-top">
                <p class="dash-kpi-label">Month revenue</p>
                <span class="dash-kpi-icon">Mo</span>
            </div>
            <p class="dash-kpi-value">{{ Money::format($monthRevenue) }}</p>
            <p class="dash-kpi-hint {{ $revenueTrend >= 0 ? 'text-emerald-600' : 'text-red-500' }} font-semibold">
                {{ $revenueTrend >= 0 ? '+' : '' }}{{ number_format($revenueTrend, 1) }}% vs last month
            </p>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-top">
                <p class="dash-kpi-label">Month orders</p>
                <span class="dash-kpi-icon">Or</span>
            </div>
            <p class="dash-kpi-value">{{ number_format($monthOrderCount) }}</p>
            <p class="dash-kpi-hint {{ $ordersTrend >= 0 ? 'text-emerald-600' : 'text-red-500' }} font-semibold">
                {{ $ordersTrend >= 0 ? '+' : '' }}{{ number_format($ordersTrend, 1) }}% vs last month
            </p>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-top">
                <p class="dash-kpi-label">Avg order value</p>
                <span class="dash-kpi-icon">Av</span>
            </div>
            <p class="dash-kpi-value">{{ Money::format($avgOrderValue) }}</p>
            <p class="dash-kpi-hint">This month average</p>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-top">
                <p class="dash-kpi-label">Customers</p>
                <span class="dash-kpi-icon">Cu</span>
            </div>
            <p class="dash-kpi-value">{{ number_format($customerCount) }}</p>
            <p class="dash-kpi-hint">{{ number_format($newCustomersThisMonth) }} new this month</p>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-top">
                <p class="dash-kpi-label">Products</p>
                <span class="dash-kpi-icon">Pr</span>
            </div>
            <p class="dash-kpi-value">{{ number_format($productCount) }}</p>
            <p class="dash-kpi-hint">{{ number_format($lowStockCount) }} low stock</p>
        </div>
    </div>

    {{-- Attention strip --}}
    <div class="dash-attention">
        @foreach ($attentionItems as $item)
            <a href="{{ $item['href'] }}" class="dash-attention-item dash-attention-item--{{ $item['tone'] }}">
                <span class="dash-attention-count">{{ number_format($item['count']) }}</span>
                <span class="dash-attention-label">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- ROW 1: Revenue + Pipeline --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-3 items-stretch">
        <section class="xl:col-span-8 dash-panel">
            <div class="dash-panel-body">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div>
                        <h2 class="admin-panel-title">Revenue overview</h2>
                        <p class="admin-panel-subtitle">Performance for the selected period</p>
                        <div class="mt-3 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <span class="text-2xl font-extrabold tracking-tight text-slate-900 tabular-nums">
                                {{ Money::format($chartTotal) }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $chartTrend >= 0 ? 'text-emerald-700 bg-emerald-50' : 'text-red-600 bg-red-50' }} px-2 py-0.5 rounded-full">
                                {{ $chartTrend >= 0 ? '+' : '−' }}{{ number_format(abs($chartTrend), 1) }}%
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium">{{ $chartTrendLabel }}</span>
                        </div>
                    </div>
                    <div class="admin-seg self-start">
                        <a href="{{ route('admin.dashboard', ['range' => '7']) }}" @class(['admin-seg-item', 'is-active' => $chartRange === '7'])>7d</a>
                        <a href="{{ route('admin.dashboard', ['range' => '30']) }}" @class(['admin-seg-item', 'is-active' => $chartRange === '30'])>30d</a>
                        <a href="{{ route('admin.dashboard', ['range' => 'year']) }}" @class(['admin-seg-item', 'is-active' => $chartRange === 'year'])>Year</a>
                    </div>
                </div>

                <div class="mt-3 w-full overflow-hidden">
                    <svg viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" class="w-full h-auto max-h-60 select-none" preserveAspectRatio="xMidYMid meet">
                        @foreach ($yTicks as $i => $tick)
                            @php $lineY = $chartTop + ($i * ($chartHeight / 4)); @endphp
                            <text x="45" y="{{ $lineY + 3 }}" font-size="10" fill="#94a3b8" font-family="sans-serif" text-anchor="end">{{ $tick['label'] }}</text>
                            <line x1="{{ $chartLeft }}" y1="{{ $lineY }}" x2="{{ $chartRight }}" y2="{{ $lineY }}" stroke="{{ $i === 4 ? '#e2e8f0' : '#f1f5f9' }}" stroke-dasharray="{{ $i === 4 ? 'none' : '3,3' }}" stroke-width="1" />
                        @endforeach

                        @php $lastIdx = count($revenueSeries) - 1; @endphp
                        @foreach ($revenueSeries as $idx => $point)
                            @php
                                $barX = $chartLeft + ($idx * $colWidth) + (($colWidth - $barWidth) / 2);
                                $val = (float) $point['total'];
                                $ratio = $yCeil > 0 ? ($val / $yCeil) : 0;
                                $barH = $val > 0 ? max(8, round($ratio * $chartHeight)) : 4;
                                $barY = $chartBottom - $barH;
                                $isPeak = ($val > 0 && ($val === $highestVal || $idx === $lastIdx));
                                $fillColor = $isPeak ? '#128200' : ($val > 0 ? '#19a800' : '#cbd5e1');
                            @endphp
                            <rect x="{{ $barX }}" y="{{ $barY }}" width="{{ $barWidth }}" height="{{ $barH }}" rx="3" ry="3" fill="{{ $fillColor }}" fill-opacity="{{ $val > 0 ? ($isPeak ? '1' : '0.85') : '0.45' }}">
                                <title>{{ $point['label'] }}: {{ Money::format($val) }} ({{ $point['count'] }} orders)</title>
                            </rect>
                        @endforeach

                        @if (count($revenueSeries) <= 7)
                            @foreach ($revenueSeries as $idx => $p)
                                @php $labelX = $chartLeft + ($idx * $colWidth) + ($colWidth / 2); @endphp
                                <text x="{{ $labelX }}" y="195" font-size="10" fill="#94a3b8" font-family="sans-serif" text-anchor="middle">{{ $p['label'] }}</text>
                            @endforeach
                        @else
                            @php
                                $sampleIndices = [0, intval(count($revenueSeries) * 0.33), intval(count($revenueSeries) * 0.66), count($revenueSeries) - 1];
                            @endphp
                            @foreach ($sampleIndices as $sIdx)
                                @if (isset($revenueSeries[$sIdx]))
                                    @php $labelX = $chartLeft + ($sIdx * $colWidth) + ($colWidth / 2); @endphp
                                    <text x="{{ $labelX }}" y="195" font-size="10" fill="#94a3b8" font-family="sans-serif" text-anchor="middle">{{ $revenueSeries[$sIdx]['label'] }}</text>
                                @endif
                            @endforeach
                        @endif
                    </svg>
                </div>
            </div>
        </section>

        <section class="xl:col-span-4 dash-panel">
            <div class="dash-panel-body flex flex-col h-full">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="admin-panel-title">Order pipeline</h2>
                        <p class="admin-panel-subtitle">{{ number_format($orderCount) }} total orders</p>
                    </div>
                    <a href="{{ route('admin.orders.index') }}" class="text-[11px] font-semibold text-sage hover:underline">All</a>
                </div>

                <div class="relative h-32 w-32 mx-auto my-4">
                    <svg viewBox="0 0 100 100" class="-rotate-90 h-full w-full">
                        <circle cx="50" cy="50" r="40" fill="none" stroke="#f1f5f9" stroke-width="10" />
                        @foreach ($donutSegments as $segment)
                            @if ($segment['dash'] > 0)
                                <circle cx="50" cy="50" r="40" fill="none" stroke="{{ $segment['color'] }}" stroke-width="10" stroke-dasharray="{{ $segment['dash'] }} {{ $segment['gap'] }}" stroke-dashoffset="{{ $segment['offset'] }}" />
                            @endif
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-xl font-extrabold text-slate-900 tabular-nums">{{ number_format($orderCount) }}</span>
                        <span class="text-[10px] text-slate-400 font-medium">orders</span>
                    </div>
                </div>

                <div class="mt-auto space-y-1.5">
                    @foreach ($pipeline as $item)
                        <a href="{{ $item['href'] }}" class="dash-pipeline-row">
                            <span class="flex items-center gap-2 min-w-0">
                                <span class="h-2 w-2 rounded-full shrink-0" style="background: {{ $item['color'] }}"></span>
                                <span class="truncate font-medium text-slate-700">{{ $item['label'] }}</span>
                            </span>
                            <span class="flex items-center gap-2 tabular-nums font-semibold shrink-0">
                                <span class="text-slate-900">{{ number_format($item['count']) }}</span>
                                <span class="text-slate-400 w-9 text-right">{{ number_format($item['percent'], 0) }}%</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    {{-- ROW 2: Recent orders + Inventory + Shortcuts --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-3 items-stretch">
        <section class="xl:col-span-7 dash-panel">
            <div class="admin-panel-header !py-3">
                <div>
                    <h2 class="admin-panel-title">Recent orders</h2>
                    <p class="admin-panel-subtitle">Latest store transactions</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-sage hover:underline">View all</a>
            </div>
            <div class="dash-panel-body !pt-0 !px-0">
                <div class="admin-table-wrap">
                    <table class="admin-table dash-orders-table min-w-[520px]">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                @php
                                    $badgeClass = match ($order->status) {
                                        'pending', 'unpaid' => 'admin-badge-warning',
                                        'processing', 'on_the_way', 'in_courier' => 'admin-badge-info',
                                        'delivered', 'completed' => 'admin-badge-success',
                                        'cancelled' => 'admin-badge-danger',
                                        default => 'admin-badge-neutral',
                                    };
                                    $initials = collect(preg_split('/\s+/', trim((string) $order->customer_name) ?: 'Guest'))
                                        ->filter()
                                        ->take(2)
                                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                        ->implode('');
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-sage hover:underline">#{{ $order->invoice_no }}</a>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600 border border-slate-200">{{ $initials ?: 'G' }}</span>
                                            <span class="truncate font-medium text-slate-800 max-w-[9rem]" title="{{ $order->customer_name }}">{{ $order->customer_name }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap text-slate-500">{{ $order->created_at?->format('d M, h:i A') }}</td>
                                    <td class="font-bold tabular-nums text-slate-900">{{ Money::format($order->total) }}</td>
                                    <td><span class="admin-badge-pill {{ $badgeClass }}">{{ $order->statusLabel() }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="admin-empty-cell">
                                        <div class="admin-empty !py-8">
                                            <p class="admin-empty-title">No recent orders</p>
                                            <p class="admin-empty-hint">New checkouts will appear here instantly.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="xl:col-span-5 grid grid-cols-1 gap-3">
            <div class="dash-panel">
                <div class="dash-panel-body">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h2 class="admin-panel-title">Inventory health</h2>
                            <p class="admin-panel-subtitle">≤ {{ $lowStockThreshold }} units flagged</p>
                        </div>
                        <span class="admin-badge-pill {{ $isStockHealthy ? 'admin-badge-success' : 'admin-badge-warning' }}">
                            {{ $isStockHealthy ? 'Healthy' : 'Attention' }}
                        </span>
                    </div>

                    <div class="mt-3">
                        <div class="flex items-center justify-between text-xs font-medium">
                            <span class="text-slate-700 font-semibold">In stock</span>
                            <span class="text-slate-900 font-bold tabular-nums">{{ $inStockCount }} / {{ $productCount }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden my-2">
                            <div class="h-full rounded-full {{ $isStockHealthy ? 'bg-sage' : 'bg-amber-500' }}" style="width: {{ $stockPercentage }}%;"></div>
                        </div>
                    </div>

                    @if ($lowStockProducts->isNotEmpty())
                        <ul class="mt-2 divide-y divide-slate-100">
                            @foreach ($lowStockProducts->take(5) as $product)
                                <li>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="flex items-center justify-between gap-2 py-2 hover:bg-slate-50 rounded-md px-1 -mx-1 transition">
                                        <span class="min-w-0">
                                            <span class="block text-xs font-semibold text-slate-800 truncate">{{ $product->name }}</span>
                                            @if ($product->sku)
                                                <span class="block text-[10px] text-slate-400 truncate">SKU {{ $product->sku }}</span>
                                            @endif
                                        </span>
                                        <span class="admin-badge {{ (int) $product->stock <= 0 ? 'admin-badge-danger' : 'admin-badge-warning' }}">
                                            {{ (int) $product->stock }} left
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        @if ($lowStockCount > 5)
                            <a href="{{ route('admin.products.index') }}" class="mt-1 inline-block text-xs font-semibold text-sage hover:underline">
                                View all {{ number_format($lowStockCount) }} low-stock
                            </a>
                        @endif
                    @else
                        <p class="mt-3 text-xs text-slate-500">All active products have healthy stock levels.</p>
                    @endif
                </div>
            </div>

            <div class="dash-panel">
                <div class="dash-panel-body">
                    <h2 class="admin-panel-title">Shortcuts</h2>
                    <p class="admin-panel-subtitle mb-3">Jump into daily work</p>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="dash-quick">
                            <span>Pending</span>
                            @if ($pendingOrderCount > 0)
                                <span class="admin-badge admin-badge-warning">{{ $pendingOrderCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('admin.orders.index', ['tab' => 'incomplete']) }}" class="dash-quick">
                            <span>Incomplete</span>
                            @if ($incompleteOrderCount > 0)
                                <span class="admin-badge admin-badge-neutral">{{ $incompleteOrderCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('admin.orders.index', ['section' => 'courier']) }}" class="dash-quick">Courier</a>
                        <a href="{{ route('admin.products.index') }}" class="dash-quick">Products</a>
                        <a href="{{ route('admin.customers.index') }}" class="dash-quick">Customers</a>
                        <a href="{{ route('admin.reports.index') }}" class="dash-quick">Reports</a>
                        <a href="{{ route('admin.refunds.index') }}" class="dash-quick">Refunds</a>
                        <a href="{{ route('admin.media.index') }}" class="dash-quick">Media</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
