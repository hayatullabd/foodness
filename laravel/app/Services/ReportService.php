<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public const EXCLUDED_REVENUE_STATUSES = ['cancelled', 'refunded'];

    public function __construct(
        private readonly GeminiService $geminiService
    ) {}

    /**
     * Resolve date range string to Carbon start and end dates.
     *
     * @return array{start: Carbon, end: Carbon, label: string, range: string}
     */
    public function resolveDateRange(string $range = '30_days', ?string $from = null, ?string $to = null): array
    {
        $now = now();

        return match ($range) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Today ('.$now->format('d M Y').')',
                'range' => 'today',
            ],
            'yesterday' => [
                'start' => $now->copy()->subDay()->startOfDay(),
                'end' => $now->copy()->subDay()->endOfDay(),
                'label' => 'Yesterday ('.$now->copy()->subDay()->format('d M Y').')',
                'range' => 'yesterday',
            ],
            '7_days' => [
                'start' => $now->copy()->subDays(6)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Last 7 Days',
                'range' => '7_days',
            ],
            'this_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'This Month ('.$now->format('F Y').')',
                'range' => 'this_month',
            ],
            'last_month' => [
                'start' => $now->copy()->subMonthNoOverflow()->startOfMonth(),
                'end' => $now->copy()->subMonthNoOverflow()->endOfMonth(),
                'label' => 'Last Month ('.$now->copy()->subMonthNoOverflow()->format('F Y').')',
                'range' => 'last_month',
            ],
            'this_year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'This Year ('.$now->format('Y').')',
                'range' => 'this_year',
            ],
            'custom' => $this->resolveCustomRange($from, $to),
            default => [
                'start' => $now->copy()->subDays(29)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Last 30 Days',
                'range' => '30_days',
            ],
        };
    }

    /**
     * Resolve custom date range.
     *
     * @return array{start: Carbon, end: Carbon, label: string, range: string}
     */
    private function resolveCustomRange(?string $from, ?string $to): array
    {
        try {
            $start = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(29)->startOfDay();
            $end = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

            if ($start->greaterThan($end)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [
                'start' => $start,
                'end' => $end,
                'label' => 'Custom Range: '.$start->format('d M Y').' - '.$end->format('d M Y'),
                'range' => 'custom',
            ];
        } catch (\Throwable) {
            return [
                'start' => now()->subDays(29)->startOfDay(),
                'end' => now()->endOfDay(),
                'label' => 'Last 30 Days',
                'range' => '30_days',
            ];
        }
    }

    /**
     * Calculate all metrics for the specified date range.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(Carbon $start, Carbon $end, string $range = '30_days', string $rangeLabel = ''): array
    {
        $baseOrdersQuery = Order::query()->whereBetween('created_at', [$start, $end]);
        $totalOrders = (clone $baseOrdersQuery)->count();

        $revenueOrdersQuery = (clone $baseOrdersQuery)->whereNotIn('status', self::EXCLUDED_REVENUE_STATUSES);
        $revenueOrdersCount = (clone $revenueOrdersQuery)->count();

        $grossSales = (float) (clone $revenueOrdersQuery)->sum('total');
        $netSubtotal = (float) (clone $revenueOrdersQuery)->sum('subtotal');
        $shippingCollected = (float) (clone $revenueOrdersQuery)->sum('shipping_charge');
        $discountsGiven = (float) (clone $revenueOrdersQuery)->sum('discount');

        $aov = $revenueOrdersCount > 0 ? round($grossSales / $revenueOrdersCount, 2) : 0.0;

        // Order Status Breakdown
        $statusCounts = (clone $baseOrdersQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $deliveredCount = (int) ($statusCounts['delivered'] ?? 0) + (int) ($statusCounts['completed'] ?? 0);
        $inCourierCount = (int) ($statusCounts['in_courier'] ?? 0) + (int) ($statusCounts['on_the_way'] ?? 0);
        $processingCount = (int) ($statusCounts['processing'] ?? 0);
        $pendingCount = (int) ($statusCounts['pending'] ?? 0);
        $cancelledCount = (int) ($statusCounts['cancelled'] ?? 0);
        $refundedCount = (int) ($statusCounts['refunded'] ?? 0);
        $returnedCount = $cancelledCount + $refundedCount;

        $deliveryRate = $totalOrders > 0 ? round(($deliveredCount / $totalOrders) * 100, 1) : 0.0;
        $returnRate = $totalOrders > 0 ? round(($returnedCount / $totalOrders) * 100, 1) : 0.0;

        // Payment Method Breakdown
        $paymentCounts = (clone $baseOrdersQuery)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as sum_total')
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($row): array {
                $method = strtolower((string) ($row->payment_method ?: 'cod'));

                return [$method => [
                    'count' => (int) $row->count,
                    'total' => (float) $row->sum_total,
                ]];
            })
            ->all();

        // Top 5 Best-Selling Products
        $topProducts = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereNotIn('orders.status', self::EXCLUDED_REVENUE_STATUSES)
            ->selectRaw('order_items.product_id, order_items.product_name, SUM(order_items.qty) as total_qty, SUM(order_items.price * order_items.qty) as total_revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Real-time Low Stock Products
        $lowStockProducts = Product::query()
            ->where('is_active', true)
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->limit(6)
            ->get(['id', 'name', 'sku', 'stock', 'price']);

        // Daily / Periodic Trends for Chart
        $trendSeries = $this->calculateTrendSeries($start, $end);

        return [
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'totalOrders' => $totalOrders,
            'revenueOrdersCount' => $revenueOrdersCount,
            'grossSales' => $grossSales,
            'netSubtotal' => $netSubtotal,
            'shippingCollected' => $shippingCollected,
            'discountsGiven' => $discountsGiven,
            'aov' => $aov,
            'deliveredCount' => $deliveredCount,
            'inCourierCount' => $inCourierCount,
            'processingCount' => $processingCount,
            'pendingCount' => $pendingCount,
            'cancelledCount' => $cancelledCount,
            'refundedCount' => $refundedCount,
            'returnedCount' => $returnedCount,
            'deliveryRate' => $deliveryRate,
            'returnRate' => $returnRate,
            'statusCounts' => $statusCounts,
            'paymentCounts' => $paymentCounts,
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts,
            'trendSeries' => $trendSeries,
            'isAiEnabled' => $this->geminiService->isEnabled(),
        ];
    }

    /**
     * Calculate daily trend series for visualization.
     *
     * @return list<array{date: string, label: string, sales: float, orders: int}>
     */
    private function calculateTrendSeries(Carbon $start, Carbon $end): array
    {
        $diffDays = $start->diffInDays($end) + 1;

        // 1. Single day range (Today / Yesterday): Hourly timeline in 3-hour intervals
        if ($diffDays <= 1) {
            $series = [];
            $current = $start->copy()->startOfDay();
            $dayEnd = $start->copy()->endOfDay();

            while ($current->lessThan($dayEnd)) {
                $slotStart = $current->copy();
                $slotEnd = $current->copy()->addHours(3)->subSecond();
                if ($slotEnd->greaterThan($dayEnd)) {
                    $slotEnd = $dayEnd->copy();
                }

                $slotOrders = Order::query()
                    ->whereBetween('created_at', [$slotStart, $slotEnd]);

                $sales = (float) (clone $slotOrders)
                    ->whereNotIn('status', self::EXCLUDED_REVENUE_STATUSES)
                    ->sum('total');

                $count = (clone $slotOrders)->count();

                $series[] = [
                    'date' => $slotStart->format('H:i'),
                    'label' => $slotStart->format('g A'),
                    'sales' => $sales,
                    'orders' => $count,
                ];

                $current->addHours(3);
            }

            return $series;
        }

        // 2. Long range (> 35 days, e.g. Year): Monthly breakdown
        if ($diffDays > 35) {
            $series = [];
            $current = $start->copy()->startOfMonth();

            while ($current->lessThanOrEqualTo($end)) {
                $monthStart = $current->copy()->startOfMonth();
                $monthEnd = $current->copy()->endOfMonth();

                $monthOrders = Order::query()
                    ->whereBetween('created_at', [$monthStart, $monthEnd]);

                $sales = (float) (clone $monthOrders)
                    ->whereNotIn('status', self::EXCLUDED_REVENUE_STATUSES)
                    ->sum('total');

                $count = (clone $monthOrders)->count();

                $series[] = [
                    'date' => $monthStart->format('Y-m'),
                    'label' => $monthStart->format('M Y'),
                    'sales' => $sales,
                    'orders' => $count,
                ];

                $current->addMonthNoOverflow()->startOfMonth();
            }

            return $series;
        }

        // 3. Multi-day range (2 to 35 days, e.g. 7 days, 30 days): Daily breakdown
        $series = [];
        $current = $start->copy();

        while ($current->lessThanOrEqualTo($end)) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();

            $dayOrders = Order::query()
                ->whereBetween('created_at', [$dayStart, $dayEnd]);

            $sales = (float) (clone $dayOrders)
                ->whereNotIn('status', self::EXCLUDED_REVENUE_STATUSES)
                ->sum('total');

            $count = (clone $dayOrders)->count();

            $series[] = [
                'date' => $current->format('Y-m-d'),
                'label' => $current->format('d M'),
                'sales' => $sales,
                'orders' => $count,
            ];

            $current->addDay();
        }

        return $series;
    }

    /**
     * Stream CSV export of orders in date range.
     */
    public function exportCsv(Carbon $start, Carbon $end, string $rangeLabel): StreamedResponse
    {
        $fileName = 'foodness-report-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($start, $end, $rangeLabel): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel Bengali font rendering
            fwrite($handle, "\xEF\xBB\xBF");

            // Executive Summary Section
            fputcsv($handle, ['FOODNESS E-COMMERCE - BUSINESS REPORT']);
            fputcsv($handle, ['Report Period:', $rangeLabel]);
            fputcsv($handle, ['Generated At:', now()->format('Y-m-d h:i A')]);
            fputcsv($handle, []);

            // Data Table Headers
            fputcsv($handle, [
                'Invoice No',
                'Order Date',
                'Customer Name',
                'Phone',
                'Address',
                'Status',
                'Payment Method',
                'Courier Provider',
                'Subtotal (BDT)',
                'Shipping Charge (BDT)',
                'Discount (BDT)',
                'Total Amount (BDT)',
            ]);

            Order::query()
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->chunk(200, function (Collection $orders) use ($handle): void {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            $order->invoice_no,
                            $order->created_at?->format('Y-m-d H:i'),
                            $order->customer_name,
                            $order->customer_phone,
                            str_replace(["\r", "\n"], ' ', (string) $order->customer_address),
                            ucfirst((string) $order->status),
                            strtoupper((string) $order->payment_method),
                            ucfirst((string) ($order->courier_provider ?: 'N/A')),
                            number_format((float) $order->subtotal, 2, '.', ''),
                            number_format((float) $order->shipping_charge, 2, '.', ''),
                            number_format((float) $order->discount, 2, '.', ''),
                            number_format((float) $order->total, 2, '.', ''),
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Generate AI Executive Insights or answer questions using GeminiService.
     *
     * @param  array<string, mixed>  $metrics
     * @param  list<array{role: string, text: string}>  $history
     */
    public function generateAiInsight(
        array $metrics,
        string $language = 'en',
        ?string $customQuestion = null,
        array $history = []
    ): string {
        if (! $this->geminiService->isEnabled()) {
            return $language === 'bn'
                ? 'Gemini AI API Key কনফিগার করা নেই। অনুগ্রহ করে Settings > API Integrations থেকে Gemini AI সচল করুন।'
                : 'Gemini AI API Key is not configured. Please enable Gemini AI in Settings > API Integrations.';
        }

        $topProductsSummary = collect($metrics['topProducts'] ?? [])
            ->map(fn ($p) => ($p->product_name ?? 'Product').' ('.($p->total_qty ?? 0).' pcs sold, BDT '.number_format((float) ($p->total_revenue ?? 0)).')')
            ->implode("\n- ");

        $lowStockSummary = collect($metrics['lowStockProducts'] ?? [])
            ->map(fn ($p) => $p->name.' (Stock: '.$p->stock.' pcs)')
            ->implode("\n- ");

        $dataContext = <<<DATA
[Store Performance Data Context]:
- Period: {$metrics['rangeLabel']}
- Total Orders: {$metrics['totalOrders']} orders
- Gross Sales: BDT {$metrics['grossSales']}
- Product Net Subtotal: BDT {$metrics['netSubtotal']}
- Shipping Fees Collected: BDT {$metrics['shippingCollected']}
- Total Discounts Given: BDT {$metrics['discountsGiven']}
- Average Order Value (AOV): BDT {$metrics['aov']}
- Delivered / Completed: {$metrics['deliveredCount']} orders ({$metrics['deliveryRate']}%)
- Currently in Courier / Transit: {$metrics['inCourierCount']} orders
- Processing & Pending: {$metrics['processingCount']} + {$metrics['pendingCount']} orders
- Cancelled & Returned: {$metrics['returnedCount']} orders ({$metrics['returnRate']}%)

[Top-Selling Products in this Period]:
- {$topProductsSummary}

[Low Stock Alert (Real-time)]:
- {$lowStockSummary}
DATA;

        // If user asked a specific conversational question
        if ($customQuestion && trim($customQuestion) !== '') {
            $prompt = <<<PROMPT
You are the Chief Business Intelligence Analyst and E-commerce Strategy Advisor for Foodness.com.

The store owner is asking you a specific business question about their store's performance.

{$dataContext}

[Store Owner's Question]:
"{$customQuestion}"

Instructions:
- If the store owner asks in Bengali or requests Bengali, answer in natural, professional, encouraging Bengali.
- If the store owner asks in English, answer in fluent, executive English.
- Base your answers strictly on the verified numbers and data provided above.
- Provide actionable recommendations, specific percentage comparisons, and practical solutions.
- Use clear Markdown formatting with bullet points and bold highlights.
PROMPT;

            $systemInstruction = 'You are an elite e-commerce strategic consultant and senior business intelligence advisor for Foodness.com. You seamlessly converse in both professional English and natural, high-level business Bengali depending on the user preference.';

            $result = $this->geminiService->generateContent($prompt, $systemInstruction);

            return $result ?: ($language === 'bn' ? 'দুঃখিত, কোনো উত্তর পাওয়া যায়নি।' : 'Unable to generate an answer from Gemini AI.');
        }

        // Standard Executive Report Generation
        if ($language === 'bn') {
            $prompt = <<<PROMPT
You are the Chief Business Intelligence Analyst and E-commerce Strategy Advisor for Foodness.com (a premier fashion and lifestyle e-commerce store in Bangladesh).

Please write an Executive Business Intelligence & Strategy Report in clear, professional, actionable Bengali based on the operational store data below:

{$dataContext}

আপনার রিপোর্টটি অবশ্যই Markdown ফরম্যাটে সাজিয়ে লিখবেন। নিচের ৪টি নির্দিষ্ট সেকশন বাধ্যতামূলক রাখুন:
### ১. 📈 পারফরম্যান্স হাইলাইটস (Performance Highlights)
বিক্রি, গড় অর্ডার ভ্যালু (AOV) ও অর্ডারের পরিমাণের বাস্তবসম্মত মূল্যায়ন করুন।

### ২. ⚠️ ঝুঁকি ও রিটার্ন অ্যানালাইসিস (Return & Delivery Bottlenecks)
ডেলিভারি সাকসেস রেট এবং বাতিল/রিটার্ন রেট ({$metrics['returnRate']}%) বিবেচনা করে কীভাবে রিটার্ন কমিয়ে ক্যাশফ্লো ঠিক রাখা যায় তা লিখুন।

### ৩. 📦 ইনভেন্টরি ও প্রোডাক্ট ইনসাইটস (Inventory & Bestsellers)
কোন প্রোডাক্টগুলোর স্টক এখনই বাড়ানো দরকার এবং কোন আইটেমে বিশেষ নজর দিতে হবে।

### ৪. 🎯 আগামী ৭ দিনের জন্য ৩টি কার্যকরী পদক্ষেপ (Actionable Strategy)
অ্যাডমিন অবিলম্বে প্রয়োগ করতে পারেন এমন ৩টি বাস্তবসম্মত কৌশল।

ভাষা সাবলীল, প্রফেশনাল ও উৎসাহব্যঞ্জক রাখুন। প্রদত্ত ভেরিফায়েড ডেটা বিশ্লেষণ করুন।
PROMPT;

            $systemInstruction = 'You are a senior e-commerce executive and data analyst providing crystal-clear, structured, highly professional Bengali executive intelligence summaries for store owners in Bangladesh.';
        } else {
            $prompt = <<<PROMPT
You are the Chief Business Intelligence Analyst and E-commerce Strategy Advisor for Foodness.com (a premier fashion and lifestyle e-commerce store in Bangladesh).

Please write an Executive Business Intelligence & Strategy Report in clear, professional, highly actionable English based on the operational store data below:

{$dataContext}

Format your report in structured Markdown with these 4 specific sections:
### 1. 📈 Performance Highlights
Analyze gross sales, net product revenue, average order value (AOV), and overall sales velocity.

### 2. ⚠️ Returns & Delivery Bottlenecks
Evaluate the delivery success rate and cancellation/return rate ({$metrics['returnRate']}%). Provide specific suggestions to reduce return rates and speed up cash flow.

### 3. 📦 Inventory & Bestseller Insights
Identify which top products require urgent stock replenishment and which catalog areas present high revenue growth.

### 4. 🎯 Actionable Strategy for the Next 7 Days
Provide 3 practical, high-impact action steps for the store owner to maximize profit and streamline order fulfillment.

Keep the tone professional, insightful, and executive-ready. Do not fabricate numbers; analyze only the verified data provided.
PROMPT;

            $systemInstruction = 'You are an elite senior e-commerce executive and business intelligence analyst. You provide crystal-clear, structured, professional English executive briefings and growth roadmaps for online store owners.';
        }

        $result = $this->geminiService->generateContent($prompt, $systemInstruction);

        return $result ?: ($language === 'bn' ? 'Gemini AI থেকে সামারি তৈরি করা সম্ভব হয়নি।' : 'Unable to generate summary from Gemini AI. Please check your internet connection and API quota.');
    }
}
