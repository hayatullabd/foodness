<?php

namespace App\Services;

use App\Models\IpBlock;
use App\Models\Order;
use App\Models\OrderBlockLog;
use App\Models\OrderItem;
use App\Models\OrderRestrictionSetting;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderRestrictionService
{
    public function __construct(
        public ClientTelemetryService $telemetryService = new ClientTelemetryService,
    ) {}

    /**
     * Fully validates an incoming checkout request against all active security vectors:
     * - IP address blacklist and rate limiting
     * - Phone number blacklist, BD operator validation, and rate limiting
     * - Device token / user-agent blacklist and rate limiting
     * - Anti-spam junk words and minimum address length
     * - Per-product order quantity limit
     *
     * Returns an error message if blocked, or null if permitted.
     *
     * @param  iterable<int, array{product_id?: int|null, name?: string|null, product?: Product|null}|Product>  $products
     * @param  array{phone?: string|null, name?: string|null, address?: string|null}  $customerData
     */
    public function validateCheckout(
        Request $request,
        iterable $products,
        array $customerData = [],
        ?int $userId = null,
    ): ?string {
        $settings = OrderRestrictionSetting::current();

        if (! $settings->enabled) {
            return null;
        }

        $phone = trim((string) ($customerData['phone'] ?? ''));
        $name = trim((string) ($customerData['name'] ?? ''));
        $address = trim((string) ($customerData['address'] ?? ''));
        $ip = (string) $request->ip();
        $userAgent = (string) $request->userAgent();
        $deviceId = (string) ($request->cookie('foodness_did') ?: $request->header('X-Device-Id') ?: $request->input('device_id') ?: '');
        $hours = max(1, (int) $settings->hours);
        $since = now()->subHours($hours);
        $payload = $this->telemetryService->buildPayload($request, $products, $customerData);

        // 1. IP LEVEL BLOCKING & RATE LIMITING
        if ($settings->block_ip_enabled && $ip !== '') {
            // Check IpBlock table
            if (IpBlock::isBlocked($ip)) {
                return $this->logBlock(
                    'ip_blacklist',
                    'Your IP address or network has been restricted from placing orders.',
                    $ip,
                    $ip,
                    $phone,
                    $deviceId,
                    $name,
                    $address,
                    $payload,
                );
            }

            // Check blocked_ips blacklist
            $blockedIps = $settings->parseList($settings->blocked_ips);
            foreach ($blockedIps as $blockedIp) {
                if ($this->matchIp($ip, $blockedIp)) {
                    return $this->logBlock(
                        'ip_blacklist',
                        'Your IP address or network has been restricted from placing orders.',
                        $blockedIp,
                        $ip,
                        $phone,
                        $deviceId,
                        $name,
                        $address,
                        $payload,
                    );
                }
            }

            // Check Max Orders per IP
            $maxPerIp = (int) $settings->max_per_ip;
            if ($maxPerIp > 0) {
                $recentIpOrders = Order::query()
                    ->where('ip_address', $ip)
                    ->where('created_at', '>=', $since)
                    ->whereNotIn('status', ['cancelled', 'refunded', 'returned', 'courier_returned'])
                    ->count();

                if ($recentIpOrders >= $maxPerIp) {
                    $msg = sprintf(
                        'Order limit reached for your network (%d orders per %d hours). Please contact support.',
                        $maxPerIp,
                        $hours,
                    );

                    return $this->logBlock(
                        'ip_rate_limit',
                        $msg,
                        (string) $maxPerIp,
                        $ip,
                        $phone,
                        $deviceId,
                        $name,
                        $address,
                        $payload,
                    );
                }
            }
        }

        // 2. PHONE LEVEL BLOCKING & VALIDATION
        if ($settings->block_phone_enabled && $phone !== '') {
            $cleanPhone = preg_replace('/[^\d]/', '', $phone);

            // Check blocked_phones blacklist
            $blockedPhones = $settings->parseList($settings->blocked_phones);
            foreach ($blockedPhones as $blockedPhone) {
                $cleanBlocked = preg_replace('/[^\d]/', '', $blockedPhone);
                if ($cleanBlocked !== '' && str_ends_with($cleanPhone, $cleanBlocked)) {
                    return $this->logBlock(
                        'phone_blacklist',
                        'This mobile number is restricted from placing orders.',
                        $blockedPhone,
                        $ip,
                        $phone,
                        $deviceId,
                        $name,
                        $address,
                        $payload,
                    );
                }
            }

            // Check Bangladesh Phone Validation (11 digits, starts with 013-019)
            if ($settings->validate_bd_phone) {
                if (! $this->isValidBangladeshiPhone($phone)) {
                    return $this->logBlock(
                        'invalid_phone',
                        'Please enter a valid 11-digit Bangladeshi mobile number (013-019).',
                        $phone,
                        $ip,
                        $phone,
                        $deviceId,
                        $name,
                        $address,
                        $payload,
                    );
                }
            }

            // Check Max Orders per Phone
            $maxPerPhone = (int) $settings->max_per_phone;
            if ($maxPerPhone > 0) {
                $recentPhoneOrders = Order::query()
                    ->where('customer_phone', $phone)
                    ->where('created_at', '>=', $since)
                    ->whereNotIn('status', ['cancelled', 'refunded', 'returned', 'courier_returned'])
                    ->count();

                if ($recentPhoneOrders >= $maxPerPhone) {
                    $msg = sprintf(
                        'Order limit reached for this mobile number (%d orders per %d hours).',
                        $maxPerPhone,
                        $hours,
                    );

                    return $this->logBlock(
                        'phone_rate_limit',
                        $msg,
                        (string) $maxPerPhone,
                        $ip,
                        $phone,
                        $deviceId,
                        $name,
                        $address,
                        $payload,
                    );
                }
            }
        }

        // 3. DEVICE LEVEL BLOCKING & RATE LIMITING
        if ($settings->block_device_enabled) {
            $blockedDevices = $settings->parseList($settings->blocked_devices);
            foreach ($blockedDevices as $signature) {
                if ($deviceId !== '' && strcasecmp($deviceId, $signature) === 0) {
                    return $this->logBlock(
                        'device_blacklist',
                        'This device has been restricted from placing orders.',
                        $deviceId,
                        $ip,
                        $phone,
                        $deviceId,
                        $name,
                        $address,
                        $payload,
                    );
                }
                if ($userAgent !== '' && stripos($userAgent, $signature) !== false) {
                    return $this->logBlock(
                        'device_blacklist',
                        'Order placement is not permitted from this browser or automated tool.',
                        $signature,
                        $ip,
                        $phone,
                        $deviceId,
                        $name,
                        $address,
                        $payload,
                    );
                }
            }

            $maxPerDevice = (int) $settings->max_per_device;
            if ($maxPerDevice > 0 && $deviceId !== '') {
                $recentDeviceOrders = Order::query()
                    ->where('device_id', $deviceId)
                    ->where('created_at', '>=', $since)
                    ->whereNotIn('status', ['cancelled', 'refunded', 'returned', 'courier_returned'])
                    ->count();

                if ($recentDeviceOrders >= $maxPerDevice) {
                    $msg = sprintf(
                        'Order limit reached for this device (%d orders per %d hours).',
                        $maxPerDevice,
                        $hours,
                    );

                    return $this->logBlock(
                        'device_rate_limit',
                        $msg,
                        (string) $maxPerDevice,
                        $ip,
                        $phone,
                        $deviceId,
                        $name,
                        $address,
                        $payload,
                    );
                }
            }
        }

        // 4. ANTI-SPAM CONTENT / FAKE ADDRESS & NAME FILTER
        if ($settings->block_words_enabled) {
            $blockedWords = $settings->parseList($settings->blocked_words);
            foreach ($blockedWords as $badWord) {
                if ($badWord === '') {
                    continue;
                }
                if (stripos($name, $badWord) !== false || stripos($address, $badWord) !== false) {
                    return $this->logBlock(
                        'spam_content',
                        'Invalid customer name or delivery address detected. Please provide accurate details.',
                        $badWord,
                        $ip,
                        $phone,
                        $deviceId,
                        $name,
                        $address,
                        $payload,
                    );
                }
            }

            $minAddressLength = (int) $settings->min_address_length;
            if ($minAddressLength > 0 && mb_strlen($address) < $minAddressLength) {
                $msg = sprintf('Delivery address must be at least %d characters with house/road/area details.', $minAddressLength);

                return $this->logBlock(
                    'short_address',
                    $msg,
                    (string) mb_strlen($address),
                    $ip,
                    $phone,
                    $deviceId,
                    $name,
                    $address,
                    $payload,
                );
            }
        }

        // 5. PER-PRODUCT PURCHASE LIMIT
        $productLimitMsg = $this->firstBlockingMessage($products, $phone, $userId);
        if ($productLimitMsg !== null) {
            return $this->logBlock(
                'product_limit',
                $productLimitMsg,
                null,
                $ip,
                $phone,
                $deviceId,
                $name,
                $address,
                $payload,
            );
        }

        return null;
    }

    /**
     * Record a block log entry safely without breaking execution.
     *
     * @param  array<string, mixed>|null  $payload
     */
    private function logBlock(
        string $blockType,
        string $message,
        ?string $offendingValue,
        ?string $ip,
        ?string $phone,
        ?string $deviceId,
        ?string $name,
        ?string $address,
        ?array $payload = null,
    ): string {
        try {
            OrderBlockLog::record(
                blockType: $blockType,
                reason: $message,
                offendingValue: $offendingValue,
                ipAddress: $ip,
                phone: $phone,
                deviceId: $deviceId,
                customerName: $name,
                customerAddress: $address,
                payload: $payload,
            );
        } catch (\Throwable) {
            // Ensure security check response is always delivered even if logging table is unavailable
        }

        return $message;
    }

    /**
     * @param  iterable<int, array{product_id?: int|null, name?: string|null, product?: Product|null}|Product>  $products
     */
    public function firstBlockingMessage(iterable $products, ?string $customerPhone, ?int $userId = null): ?string
    {
        $settings = OrderRestrictionSetting::current();

        if (! $settings->enabled) {
            return null;
        }

        $items = $this->normalizeProducts($products);

        if ($items === []) {
            return null;
        }

        $hours = max(1, (int) $settings->hours);
        $max = max(1, (int) $settings->max_per_product);
        $since = now()->subHours($hours);

        $counts = $this->queryRecentCounts(array_keys($items), $customerPhone, $userId, $since);

        foreach ($items as $productId => $name) {
            $priorOrders = $counts[$productId] ?? 0;
            if ($priorOrders >= $max) {
                return $this->formatMessage($name, $priorOrders, $max, $hours);
            }
        }

        return null;
    }

    /**
     * Strict Bangladeshi mobile operator validator:
     * Accepts:
     *   013XXXXXXXX, 014XXXXXXXX, 015XXXXXXXX, 016XXXXXXXX, 017XXXXXXXX, 018XXXXXXXX, 019XXXXXXXX
     *   Also supports +880 or 880 prefix.
     * Rejects:
     *   Non-operator codes (010, 011, 012)
     *   Dummy repeating numbers (e.g. 01711111111, 01700000000, 01800000000)
     */
    public function isValidBangladeshiPhone(string $phone): bool
    {
        $clean = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($clean, '880')) {
            $clean = substr($clean, 2);
        }

        if (strlen($clean) !== 11) {
            return false;
        }

        // Must start with 013, 014, 015, 016, 017, 018, 019
        if (! preg_match('/^01[3-9]\d{8}$/', $clean)) {
            return false;
        }

        // Reject if all 11 digits are identical (e.g. 00000000000, 11111111111)
        if (preg_match('/^(\d)\1{10}$/', $clean)) {
            return false;
        }

        // Reject obvious fake trailing repetitive pattern (e.g. 01700000000 or 01711111111)
        $trailing8 = substr($clean, 3);
        if (preg_match('/^(\d)\1{7}$/', $trailing8)) {
            return false;
        }

        return true;
    }

    /**
     * IP matcher supporting exact IPs, wildcards (e.g. 192.168.1.*) and CIDR notation (e.g. 10.0.0.0/24).
     */
    public function matchIp(string $clientIp, string $pattern): bool
    {
        $pattern = trim($pattern);
        $clientIp = trim($clientIp);

        if ($pattern === '' || $clientIp === '') {
            return false;
        }

        // Direct exact match
        if (strcasecmp($clientIp, $pattern) === 0) {
            return true;
        }

        // Wildcard match (e.g. 192.168.1.*)
        if (str_contains($pattern, '*')) {
            $regex = '/^'.str_replace(['.', '*'], ['\.', '.*'], $pattern).'$/i';

            return (bool) preg_match($regex, $clientIp);
        }

        // CIDR match (e.g. 192.168.1.0/24)
        if (str_contains($pattern, '/')) {
            return $this->cidrMatch($clientIp, $pattern);
        }

        return false;
    }

    /**
     * CIDR IPv4 matching.
     */
    private function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = (int) $mask;

        if ($mask < 0 || $mask > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $netmask = ~((1 << (32 - $mask)) - 1);

        return ($ipLong & $netmask) === ($subnetLong & $netmask);
    }

    /**
     * @param  iterable<int, array{product_id?: int|null, name?: string|null, product?: Product|null}|Product>  $products
     * @return array<int, string>
     */
    private function normalizeProducts(iterable $products): array
    {
        $normalized = [];

        foreach ($products as $item) {
            if ($item instanceof Product) {
                if ($item->id) {
                    $normalized[(int) $item->id] = (string) $item->name;
                }

                continue;
            }

            if (is_array($item)) {
                $id = $item['product_id'] ?? ($item['product']->id ?? null);
                $name = $item['name'] ?? ($item['product']->name ?? null);

                if ($id) {
                    $normalized[(int) $id] = (string) ($name ?? ('Product #'.$id));
                }
            }
        }

        return $normalized;
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, int>
     */
    private function queryRecentCounts(
        array $productIds,
        ?string $customerPhone,
        ?int $userId,
        \DateTimeInterface $since,
    ): array {
        $phone = trim((string) $customerPhone);

        if ($phone === '' && ! $userId) {
            return [];
        }

        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('order_items.product_id', $productIds)
            ->where('orders.created_at', '>=', $since)
            ->whereNotIn('orders.status', ['cancelled', 'refunded', 'returned', 'courier_returned']);

        $query->where(function ($sub) use ($phone, $userId): void {
            $applied = false;
            if ($phone !== '') {
                $sub->where('orders.customer_phone', $phone);
                $applied = true;
            }
            if ($userId) {
                if ($applied) {
                    $sub->orWhere('orders.user_id', $userId);
                } else {
                    $sub->where('orders.user_id', $userId);
                }
            }
        });

        /** @var array<int, int> $results */
        $results = $query
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id, COUNT(DISTINCT orders.id) as aggregate_count')
            ->pluck('aggregate_count', 'order_items.product_id')
            ->map(fn ($count): int => (int) $count)
            ->all();

        return $results;
    }

    private function formatMessage(string $productName, int $priorOrders, int $max, int $hours): string
    {
        return sprintf(
            'You have already placed %d order(s) for "%s" within the last %d hour(s). The limit is %d order(s).',
            $priorOrders,
            $productName,
            $hours,
            $max,
        );
    }
}
