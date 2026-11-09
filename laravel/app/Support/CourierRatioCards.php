<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Support\Collection;

class CourierRatioCards
{
    /** @var list<string> */
    private const COURIER_KEYS = [
        'pathao',
        'steadfast',
        'parceldex',
        'courrierfast',
        'redx',
        'paperfly',
        'carrybee',
    ];

    /** @var array<string, string> */
    private const HEADER_COLORS = [
        'our_record' => '#5b21b6',
        'overall' => '#6d28d9',
        'pathao' => '#e11d48',
        'steadfast' => '#0f766e',
        'redx' => '#dc2626',
        'paperfly' => '#ca8a04',
        'parceldex' => '#2563eb',
        'courrierfast' => '#0891b2',
        'carrybee' => '#d97706',
    ];

    /** @var array<string, string> */
    private const STATUS_DOT_COLORS = [
        'pending' => '#f59e0b',
        'confirmed' => '#10b981',
        'incomplete_to_confirmed' => '#0d9488',
        'processing' => '#3b82f6',
        'on_hold' => '#f97316',
        'no_response' => '#64748b',
        'on_the_way' => '#8b5cf6',
        'in_courier' => '#06b6d4',
        'delivered' => '#16a34a',
        'completed' => '#22c55e',
        'unpaid' => '#f97316',
        'refunded' => '#ea580c',
        'returned' => '#ea580c',
        'courier_returned' => '#dc2626',
        'cancelled' => '#ef4444',
    ];

    /**
     * @param  array<string, mixed>|null  $fraudReport
     * @return list<array{
     *     key: string,
     *     name: string,
     *     logo: string|null,
     *     header_color: string,
     *     success_rate: string|null,
     *     success_rate_value: float,
     *     total: int|string,
     *     success: int|string,
     *     cancelled: int|string
     * }>
     */
    public static function courierCards(?array $fraudReport): array
    {
        $fraudReport = is_array($fraudReport) ? $fraudReport : [];
        $fraudData = is_array($fraudReport['data'] ?? null) ? $fraudReport['data'] : [];
        $summary = is_array($fraudData['summary'] ?? null) ? $fraudData['summary'] : [];

        $cards = [];

        if ($summary !== []) {
            $cards[] = self::mapCourierCard('overall', [
                'name' => 'Overall',
                'logo' => null,
                'total_parcel' => $summary['total_parcel'] ?? 0,
                'success_parcel' => $summary['success_parcel'] ?? 0,
                'cancelled_parcel' => $summary['cancelled_parcel'] ?? 0,
                'success_ratio' => $summary['success_ratio'] ?? null,
            ]);
        }

        foreach (self::COURIER_KEYS as $key) {
            $courier = $fraudData[$key] ?? null;

            if (! is_array($courier)) {
                continue;
            }

            $cards[] = self::mapCourierCard($key, $courier);
        }

        return $cards;
    }

    /**
     * @return array<string, mixed>
     */
    public static function zeroFraudReport(): array
    {
        return [
            'data' => [
                'summary' => [
                    'name' => 'Overall',
                    'total_parcel' => 0,
                    'success_parcel' => 0,
                    'cancelled_parcel' => 0,
                    'success_ratio' => '0%',
                ],
                'steadfast' => [
                    'name' => 'Steadfast',
                    'total_parcel' => 0,
                    'success_parcel' => 0,
                    'cancelled_parcel' => 0,
                    'success_ratio' => '0%',
                ],
                'pathao' => [
                    'name' => 'Pathao',
                    'total_parcel' => 0,
                    'success_parcel' => 0,
                    'cancelled_parcel' => 0,
                    'success_ratio' => '0%',
                ],
                'redx' => [
                    'name' => 'RedX',
                    'total_parcel' => 0,
                    'success_parcel' => 0,
                    'cancelled_parcel' => 0,
                    'success_ratio' => '0%',
                ],
                'paperfly' => [
                    'name' => 'Paperfly',
                    'total_parcel' => 0,
                    'success_parcel' => 0,
                    'cancelled_parcel' => 0,
                    'success_ratio' => '0%',
                ],
            ],
        ];
    }

    /**
     * Compact success-rate summary for dense order list rows.
     *
     * @param  array<string, mixed>|null  $fraudReport
     * @return array{
     *     has_data: bool,
     *     success_rate: float|null,
     *     success: int|null,
     *     total: int|null,
     *     cancelled: int|null
     * }
     */
    public static function listSummary(?array $fraudReport, mixed $fraudScore = null): array
    {
        $fraudReport = is_array($fraudReport) ? $fraudReport : [];
        $fraudData = is_array($fraudReport['data'] ?? null) ? $fraudReport['data'] : [];
        $summary = is_array($fraudData['summary'] ?? null) ? $fraudData['summary'] : [];

        $total = self::parcelCount($summary['total_parcel'] ?? null);
        $success = self::parcelCount($summary['success_parcel'] ?? null);
        $cancelled = self::parcelCount($summary['cancelled_parcel'] ?? null);
        $ratio = self::ratioValue($fraudScore);

        if ($ratio === null && $summary !== []) {
            $ratio = self::resolveSuccessRatio($total ?? 0, $success, $summary['success_ratio'] ?? null);
        }

        return [
            'has_data' => $ratio !== null || $total !== null || $success !== null,
            'success_rate' => $ratio,
            'success' => $success,
            'total' => $total,
            'cancelled' => $cancelled,
        ];
    }

    /**
     * @return array{
     *     is_new_customer: bool,
     *     total_orders: int,
     *     status_counts: list<array{slug: string, label: string, count: int, dot_color: string}>
     * }
     */
    public static function ourRecordForPhone(?string $phone): array
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return self::emptyOurRecord();
        }

        return self::ourRecordsForPhones([$phone])[$phone] ?? self::emptyOurRecord();
    }

    /**
     * @param  iterable<int, string|null>  $phones
     * @return array<string, array{
     *     is_new_customer: bool,
     *     total_orders: int,
     *     status_counts: list<array{slug: string, label: string, count: int, dot_color: string}>
     * }>
     */
    public static function ourRecordsForPhones(iterable $phones): array
    {
        $normalized = Collection::make($phones)
            ->map(fn ($phone): string => trim((string) $phone))
            ->filter()
            ->unique()
            ->values();

        $empty = self::emptyOurRecord();
        $result = [];

        foreach ($normalized as $phone) {
            $result[$phone] = $empty;
        }

        if ($normalized->isEmpty()) {
            return $result;
        }

        $statusLabels = OrderStatus::query()
            ->ordered()
            ->pluck('name', 'slug');

        $rows = Order::query()
            ->selectRaw('customer_phone, status, COUNT(*) as aggregate')
            ->whereIn('customer_phone', $normalized->all())
            ->groupBy('customer_phone', 'status')
            ->get();

        /** @var array<string, array<string, int>> $countsByPhone */
        $countsByPhone = [];

        foreach ($rows as $row) {
            $phone = (string) $row->customer_phone;
            $status = (string) $row->status;
            $countsByPhone[$phone][$status] = (int) $row->aggregate;
        }

        foreach ($normalized as $phone) {
            $statusMap = $countsByPhone[$phone] ?? [];
            $total = array_sum($statusMap);
            $statusCounts = [];

            foreach ($statusMap as $slug => $count) {
                if ($count < 1) {
                    continue;
                }

                $statusCounts[] = [
                    'slug' => $slug,
                    'label' => (string) ($statusLabels[$slug] ?? str($slug)->replace('_', ' ')->title()),
                    'count' => $count,
                    'dot_color' => self::STATUS_DOT_COLORS[$slug] ?? '#64748b',
                ];
            }

            usort($statusCounts, function (array $a, array $b) use ($statusLabels): int {
                $orderA = array_search($a['slug'], $statusLabels->keys()->all(), true);
                $orderB = array_search($b['slug'], $statusLabels->keys()->all(), true);

                return ($orderA === false ? 999 : $orderA) <=> ($orderB === false ? 999 : $orderB);
            });

            $result[$phone] = [
                'is_new_customer' => $total <= 1,
                'total_orders' => $total,
                'status_counts' => $statusCounts,
            ];
        }

        return $result;
    }

    public static function headerColor(string $key): string
    {
        return self::HEADER_COLORS[$key] ?? '#475569';
    }

    /**
     * @param  array<string, mixed>  $courier
     * @return array{
     *     key: string,
     *     name: string,
     *     logo: string|null,
     *     header_color: string,
     *     success_rate: string|null,
     *     success_rate_value: float,
     *     total: int|string,
     *     success: int|string,
     *     cancelled: int|string
     * }
     */
    private static function mapCourierCard(string $key, array $courier): array
    {
        $total = self::parcelCount($courier['total_parcel'] ?? null) ?? 0;
        $success = self::parcelCount($courier['success_parcel'] ?? null);
        $cancelled = self::parcelCount($courier['cancelled_parcel'] ?? null) ?? 0;
        $ratioValue = self::resolveSuccessRatio($total, $success, $courier['success_ratio'] ?? null);

        return [
            'key' => $key,
            'name' => (string) ($courier['name'] ?? str($key)->title()),
            'logo' => is_string($courier['logo'] ?? null) ? $courier['logo'] : null,
            'header_color' => self::headerColor($key),
            'success_rate' => self::formatRatioLabel($ratioValue),
            'success_rate_value' => $ratioValue,
            'total' => $total,
            'success' => $success ?? 0,
            'cancelled' => $cancelled,
        ];
    }

    /**
     * Prefer (success / total) * 100 when parcel counts exist.
     * Fall back to API success_ratio (including when total is 0 — SteadFast often omits counts).
     */
    private static function resolveSuccessRatio(int $total, ?int $success, mixed $apiRatio): float
    {
        if ($total > 0 && $success !== null) {
            return max(0, min(100, round(($success / $total) * 100, 2)));
        }

        return self::ratioValue($apiRatio) ?? 0.0;
    }

    private static function formatRatioLabel(float $ratioValue): string
    {
        return rtrim(rtrim(number_format($ratioValue, 2, '.', ''), '0'), '.').'%';
    }

    private static function parcelCount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private static function ratioValue(mixed $ratio): ?float
    {
        if ($ratio === null || $ratio === '') {
            return null;
        }

        if (is_string($ratio)) {
            $ratio = rtrim(trim($ratio), '%');
        }

        if (! is_numeric($ratio)) {
            return null;
        }

        return max(0, min(100, round((float) $ratio, 2)));
    }

    /**
     * @return array{
     *     is_new_customer: bool,
     *     total_orders: int,
     *     status_counts: list<array{slug: string, label: string, count: int, dot_color: string}>
     * }
     */
    private static function emptyOurRecord(): array
    {
        return [
            'is_new_customer' => true,
            'total_orders' => 0,
            'status_counts' => [],
        ];
    }
}
