<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IncompleteOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\SeoSetting;
use App\Models\User;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class DashboardController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;

    private const EXCLUDED_REVENUE_STATUSES = ['cancelled', 'refunded'];

    public function index(Request $request): View
    {
        $range = in_array($request->string('range')->toString(), ['7', '30', 'year'], true)
            ? $request->string('range')->toString()
            : '30';

        $stats = Cache::remember('dashboard.stats.v2.'.$range, 300, function () use ($range): array {
            $today = now()->toDateString();
            $monthStart = now()->copy()->startOfMonth();
            $prevMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth();
            $prevMonthEnd = now()->copy()->subMonthNoOverflow()->endOfMonth();

            $statusCounts = Order::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->all();

            $todayOrderCount = Order::query()->whereDate('created_at', $today)->count();

            $todayRevenue = $this->revenueQuery()
                ->whereDate('created_at', $today)
                ->sum('total');

            $monthRevenue = $this->revenueQuery()
                ->where('created_at', '>=', $monthStart)
                ->sum('total');

            $prevMonthRevenue = $this->revenueQuery()
                ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
                ->sum('total');

            $monthOrderCount = Order::query()
                ->where('created_at', '>=', $monthStart)
                ->count();

            $prevMonthOrderCount = Order::query()
                ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
                ->count();

            $productCount = Product::query()->count();
            $prevMonthProductCount = Product::query()
                ->where('created_at', '<', $monthStart)
                ->count();

            $customerCount = User::query()->count();
            $newCustomersThisMonth = User::query()
                ->where('created_at', '>=', $monthStart)
                ->count();
            $newCustomersPrevMonth = User::query()
                ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
                ->count();

            return [
                'statusCounts' => $statusCounts,
                'todayOrderCount' => $todayOrderCount,
                'todayRevenue' => $todayRevenue,
                'monthRevenue' => $monthRevenue,
                'prevMonthRevenue' => $prevMonthRevenue,
                'monthOrderCount' => $monthOrderCount,
                'prevMonthOrderCount' => $prevMonthOrderCount,
                'productCount' => $productCount,
                'prevMonthProductCount' => $prevMonthProductCount,
                'customerCount' => $customerCount,
                'newCustomersThisMonth' => $newCustomersThisMonth,
                'newCustomersPrevMonth' => $newCustomersPrevMonth,
                'revenueSeries' => $this->revenueSeries($range),
                'previousSeriesTotal' => $this->previousSeriesTotal($range),
            ];
        });

        extract($stats);

        $pendingOrderCount = (int) ($statusCounts['pending'] ?? 0);
        $inProgressOrderCount = (int) ($statusCounts['processing'] ?? 0)
            + (int) ($statusCounts['on_the_way'] ?? 0)
            + (int) ($statusCounts['in_courier'] ?? 0);

        $deliveredOrderCount = (int) ($statusCounts['delivered'] ?? 0)
            + (int) ($statusCounts['completed'] ?? 0);

        $orderCount = (int) array_sum($statusCounts);

        $statusBreakdown = $this->statusBreakdown(
            pending: $pendingOrderCount,
            processing: $inProgressOrderCount,
            delivered: $deliveredOrderCount,
            total: $orderCount,
        );

        $chartTotal = collect($revenueSeries)->sum('total');
        $chartTrend = $this->trendPercent((float) $chartTotal, (float) ($previousSeriesTotal ?? 0));

        $lowStockQuery = Product::query()
            ->where('is_active', true)
            ->where('stock', '<=', self::LOW_STOCK_THRESHOLD);

        $lowStockCount = (clone $lowStockQuery)->count();
        $lowStockProducts = (clone $lowStockQuery)
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'sku', 'stock']);

        $admin = $request->user('admin');
        $hour = (int) now()->format('G');
        $greeting = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        return view('admin.dashboard', [
            'greeting' => $greeting.', '.($admin?->name ?: 'Admin'),
            'orderCount' => $orderCount,
            'pendingOrderCount' => $pendingOrderCount,
            'inProgressOrderCount' => $inProgressOrderCount,
            'deliveredOrderCount' => $deliveredOrderCount,
            'todayOrderCount' => $todayOrderCount,
            'todayRevenue' => (float) $todayRevenue,
            'monthRevenue' => (float) $monthRevenue,
            'monthOrderCount' => $monthOrderCount,
            'revenueTrend' => $this->trendPercent((float) $monthRevenue, (float) $prevMonthRevenue),
            'chartTrend' => $chartTrend,
            'ordersTrend' => $this->trendPercent((float) $monthOrderCount, (float) $prevMonthOrderCount),
            'productsTrend' => $this->trendPercent((float) $productCount, (float) $prevMonthProductCount),
            'customersTrend' => $this->trendPercent((float) $newCustomersThisMonth, (float) $newCustomersPrevMonth),
            'productCount' => $productCount,
            'customerCount' => $customerCount,
            'newCustomersThisMonth' => $newCustomersThisMonth,
            'incompleteOrderCount' => IncompleteOrder::query()->count(),
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
            'lowStockCount' => $lowStockCount,
            'lowStockProducts' => $lowStockProducts,
            'statusBreakdown' => $statusBreakdown,
            'revenueSeries' => $revenueSeries,
            'chartTotal' => (float) $chartTotal,
            'chartRange' => $range,
            'chartTrendLabel' => match ($range) {
                '7' => 'vs prior 7 days',
                'year' => 'vs prior 12 months',
                default => 'vs prior 30 days',
            },
            'recentOrders' => Order::query()
                ->with('orderStatus')
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }

    public function clearCache(): RedirectResponse
    {
        try {
            Artisan::call('optimize:clear');
            StoreSettings::forget();
            SeoSetting::forgetCache();
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            Cache::forget('dashboard.stats.7');
            Cache::forget('dashboard.stats.30');
            Cache::forget('dashboard.stats.year');
            Cache::forget('dashboard.stats.v2.7');
            Cache::forget('dashboard.stats.v2.30');
            Cache::forget('dashboard.stats.v2.year');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->with('error', 'Cache clear failed: '.$exception->getMessage());
        }

        return back()
            ->with('success', 'Application cache cleared successfully.');
    }

    private function revenueQuery()
    {
        return Order::query()->whereNotIn('status', self::EXCLUDED_REVENUE_STATUSES);
    }

    private function previousSeriesTotal(string $range): float
    {
        if ($range === 'year') {
            $start = now()->copy()->subMonths(23)->startOfMonth();
            $end = now()->copy()->subMonths(12)->endOfMonth();
        } elseif ($range === '7') {
            $start = now()->copy()->subDays(13)->startOfDay();
            $end = now()->copy()->subDays(7)->endOfDay();
        } else {
            $start = now()->copy()->subDays(59)->startOfDay();
            $end = now()->copy()->subDays(30)->endOfDay();
        }

        return (float) $this->revenueQuery()
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');
    }

    /**
     * @return list<array{label: string, total: float, count: int}>
     */
    private function revenueSeries(string $range): array
    {
        if ($range === 'year') {
            $start = now()->copy()->subMonths(11)->startOfMonth();
            $end = now()->copy()->endOfMonth();

            $revenueRows = $this->revenueQuery()
                ->selectRaw('YEAR(created_at) as yr, MONTH(created_at) as mo, SUM(total) as total')
                ->whereBetween('created_at', [$start, $end])
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->get()
                ->keyBy(fn ($row) => $row->yr.'-'.str_pad((string) $row->mo, 2, '0', STR_PAD_LEFT));

            $countRows = Order::query()
                ->selectRaw('YEAR(created_at) as yr, MONTH(created_at) as mo, COUNT(*) as cnt')
                ->whereBetween('created_at', [$start, $end])
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->get()
                ->keyBy(fn ($row) => $row->yr.'-'.str_pad((string) $row->mo, 2, '0', STR_PAD_LEFT));

            $points = [];

            for ($i = 11; $i >= 0; $i--) {
                $month = now()->copy()->startOfMonth()->subMonths($i);
                $key = $month->format('Y-m');

                $points[] = [
                    'label' => $month->format('M'),
                    'total' => (float) ($revenueRows[$key]->total ?? 0),
                    'count' => (int) ($countRows[$key]->cnt ?? 0),
                ];
            }

            return $points;
        }

        $days = $range === '7' ? 7 : 30;
        $start = now()->copy()->subDays($days - 1)->startOfDay();
        $end = now()->copy()->endOfDay();

        $revenueRows = $this->revenueQuery()
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('day');

        $countRows = Order::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('day');

        $points = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->copy()->subDays($i)->startOfDay();
            $key = $day->toDateString();

            $points[] = [
                'label' => $day->format($days === 7 ? 'D' : 'j'),
                'total' => (float) ($revenueRows[$key]->total ?? 0),
                'count' => (int) ($countRows[$key]->cnt ?? 0),
            ];
        }

        return $points;
    }

    /**
     * @return list<array{key: string, label: string, count: int, percent: float, color: string}>
     */
    private function statusBreakdown(int $pending, int $processing, int $delivered, int $total): array
    {
        $segments = [
            ['key' => 'delivered', 'label' => 'Delivered', 'count' => $delivered, 'color' => '#3d5a4c'],
            ['key' => 'processing', 'label' => 'Processing', 'count' => $processing, 'color' => '#0284c7'],
            ['key' => 'pending', 'label' => 'Pending', 'count' => $pending, 'color' => '#d97706'],
        ];

        $other = max(0, $total - $pending - $processing - $delivered);

        if ($other > 0) {
            $segments[] = ['key' => 'other', 'label' => 'Other', 'count' => $other, 'color' => '#94a3b8'];
        }

        return array_map(function (array $segment) use ($total): array {
            $segment['percent'] = $total > 0
                ? round(($segment['count'] / $total) * 100, 1)
                : 0.0;

            return $segment;
        }, $segments);
    }

    private function trendPercent(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
