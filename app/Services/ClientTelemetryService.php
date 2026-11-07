<?php

namespace App\Services;

use App\Models\Product;
use App\Support\ClientIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ClientTelemetryService
{
    /**
     * Build a rich audit payload containing device telemetry, parsed user-agent,
     * network geolocation intelligence, and order attempt context.
     *
     * @param  iterable<int, array{product_id?: int|null, name?: string|null, product?: Product|null, quantity?: int|null, price?: float|int|null}|Product>  $products
     * @param  array{phone?: string|null, name?: string|null, address?: string|null, notes?: string|null, email?: string|null}  $customerData
     * @return array<string, mixed>
     */
    public function buildPayload(
        Request $request,
        iterable $products = [],
        array $customerData = [],
    ): array {
        $ip = ClientIp::resolve($request);
        $userAgent = (string) $request->userAgent();
        $deviceId = (string) ($request->cookie('foodness_did') ?: $request->header('X-Device-Id') ?: $request->input('device_id') ?: '');

        $hardware = $this->extractHardwareTelemetry($request);
        $browserInfo = $this->parseUserAgent($userAgent);
        $geo = $this->resolveIpLocation($ip, $request);
        $cartSummary = $this->summarizeCartItems($products);

        return [
            'device' => [
                'token' => $deviceId ?: null,
                'screen' => $hardware['screen'] ?? null,
                'color_depth' => $hardware['color_depth'] ?? null,
                'pixel_ratio' => $hardware['pixel_ratio'] ?? null,
                'gpu_vendor' => $hardware['gpu_vendor'] ?? null,
                'gpu_renderer' => $hardware['gpu_renderer'] ?? null,
                'cores' => $hardware['cores'] ?? null,
                'memory_gb' => $hardware['memory_gb'] ?? null,
                'touch_points' => $hardware['touch_points'] ?? null,
                'platform' => $hardware['platform'] ?? null,
                'timezone' => $hardware['timezone'] ?? null,
                'language' => $hardware['language'] ?? ($request->header('Accept-Language') ?: null),
                'audio_sig' => $hardware['audio_sig'] ?? null,
                'canvas_sig' => $hardware['canvas_sig'] ?? null,
            ],
            'browser' => [
                'name' => $browserInfo['browser'],
                'version' => $browserInfo['browser_version'],
                'os' => $browserInfo['os'],
                'os_version' => $browserInfo['os_version'],
                'device_type' => $browserInfo['device_type'],
                'user_agent' => $userAgent ?: 'Unknown',
            ],
            'network' => [
                'ip' => $ip,
                'is_local' => $geo['is_local'] ?? false,
                'country' => $geo['country'] ?? 'Bangladesh',
                'country_code' => $geo['country_code'] ?? 'BD',
                'city' => $geo['city'] ?? 'Dhaka',
                'region' => $geo['region'] ?? 'Dhaka Division',
                'isp' => $geo['isp'] ?? 'Broadband / Cellular',
                'org' => $geo['org'] ?? 'Internet Provider',
                'forwarded_for' => $request->header('X-Forwarded-For'),
                'cf_ray' => $request->header('CF-Ray'),
            ],
            'attempt' => [
                'customer_name' => $customerData['name'] ?? null,
                'customer_phone' => $customerData['phone'] ?? null,
                'customer_address' => $customerData['address'] ?? null,
                'cart_items' => $cartSummary['items'],
                'items_count' => $cartSummary['count'],
                'total_amount' => $cartSummary['total'],
                'url' => $request->fullUrl(),
                'referer' => $request->header('Referer'),
                'method' => $request->method(),
                'captured_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Extract client-side hardware telemetry from inputs or cookies.
     *
     * @return array<string, mixed>
     */
    public function extractHardwareTelemetry(Request $request): array
    {
        $raw = $request->input('device_telemetry');

        if (! is_string($raw) || $raw === '') {
            $raw = $request->cookie('foodness_dt');
        }

        if (! is_string($raw) || $raw === '') {
            $raw = $request->header('X-Device-Telemetry');
        }

        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            // In case of URL encoded cookie
            $urlDecoded = json_decode(urldecode($raw), true);
            if (is_array($urlDecoded)) {
                return $urlDecoded;
            }
        } catch (\Throwable) {
            return [];
        }

        return [];
    }

    /**
     * Parse User-Agent string to detect OS, Browser, Version, and Device Type.
     *
     * @return array{browser: string, browser_version: string, os: string, os_version: string, device_type: string}
     */
    public function parseUserAgent(?string $userAgent): array
    {
        $ua = trim((string) $userAgent);

        if ($ua === '') {
            return [
                'browser' => 'Unknown Browser',
                'browser_version' => '0',
                'os' => 'Unknown OS',
                'os_version' => '0',
                'device_type' => 'Desktop',
            ];
        }

        // 1. Device Type
        $deviceType = 'Desktop';
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $deviceType = 'Tablet';
        } elseif (preg_match('/(mobile|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop)/i', $ua)) {
            $deviceType = 'Mobile';
        } elseif (preg_match('/(bot|crawl|spider|slurp|facebookexternalhit|curl|wget)/i', $ua)) {
            $deviceType = 'Bot';
        }

        // 2. Operating System
        $os = 'Unknown OS';
        $osVersion = '';

        if (preg_match('/windows nt 10\.0/i', $ua)) {
            $os = 'Windows 10 / 11';
        } elseif (preg_match('/windows nt 6\.3/i', $ua)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6\.2/i', $ua)) {
            $os = 'Windows 8';
        } elseif (preg_match('/windows nt 6\.1/i', $ua)) {
            $os = 'Windows 7';
        } elseif (preg_match('/android\s*([0-9\.]+)/i', $ua, $m)) {
            $os = 'Android';
            $osVersion = $m[1] ?? '';
        } elseif (preg_match('/iphone os\s*([0-9_]+)/i', $ua, $m)) {
            $os = 'iOS (iPhone)';
            $osVersion = str_replace('_', '.', $m[1] ?? '');
        } elseif (preg_match('/ipad;\s*cpu os\s*([0-9_]+)/i', $ua, $m)) {
            $os = 'iOS (iPad)';
            $osVersion = str_replace('_', '.', $m[1] ?? '');
        } elseif (preg_match('/mac os x\s*([0-9_\.]+)/i', $ua, $m)) {
            $os = 'macOS';
            $osVersion = str_replace('_', '.', $m[1] ?? '');
        } elseif (preg_match('/cros/i', $ua)) {
            $os = 'Chrome OS';
        } elseif (preg_match('/linux/i', $ua)) {
            $os = 'Linux';
        }

        // 3. Browser
        $browser = 'Unknown Browser';
        $browserVersion = '';

        if (preg_match('/edg\/([0-9\.]+)/i', $ua, $m)) {
            $browser = 'Microsoft Edge';
            $browserVersion = $m[1] ?? '';
        } elseif (preg_match('/samsungbrowser\/([0-9\.]+)/i', $ua, $m)) {
            $browser = 'Samsung Internet';
            $browserVersion = $m[1] ?? '';
        } elseif (preg_match('/ucbrowser\/([0-9\.]+)/i', $ua, $m)) {
            $browser = 'UC Browser';
            $browserVersion = $m[1] ?? '';
        } elseif (preg_match('/opr\/([0-9\.]+)/i', $ua, $m) || preg_match('/opera\/([0-9\.]+)/i', $ua, $m)) {
            $browser = 'Opera';
            $browserVersion = $m[1] ?? '';
        } elseif (preg_match('/chrome\/([0-9\.]+)/i', $ua, $m)) {
            $browser = 'Google Chrome';
            $browserVersion = $m[1] ?? '';
        } elseif (preg_match('/firefox\/([0-9\.]+)/i', $ua, $m)) {
            $browser = 'Mozilla Firefox';
            $browserVersion = $m[1] ?? '';
        } elseif (preg_match('/version\/([0-9\.]+).*safari/i', $ua, $m)) {
            $browser = 'Apple Safari';
            $browserVersion = $m[1] ?? '';
        }

        return [
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'os' => $os,
            'os_version' => $osVersion,
            'device_type' => $deviceType,
        ];
    }

    /**
     * @return array{browser: string, browser_version: string, os: string, os_version: string, device_type: string}
     */
    public function describeUserAgent(?string $userAgent): array
    {
        return $this->parseUserAgent((string) $userAgent);
    }

    /**
     * Resolve IP Geolocation and ISP information safely with caching and fallbacks.
     *
     * @return array{is_local: bool, is_cloudflare: bool, country: string, country_code: string, city: string, region: string, isp: string, org: string}
     */
    public function resolveIpLocation(?string $ip, ?Request $request = null): array
    {
        $ip = trim((string) $ip);

        // Check if local / loopback / private IP
        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1' || $this->isPrivateIp($ip)) {
            return [
                'is_local' => true,
                'is_cloudflare' => false,
                'country' => 'Bangladesh (Local Dev)',
                'country_code' => 'BD',
                'city' => 'Localhost / Office LAN',
                'region' => 'Development',
                'isp' => 'Internal LAN / Localhost',
                'org' => 'Developer Machine',
            ];
        }

        $cfCountry = strtoupper(trim((string) $request?->header('CF-IPCountry')));
        if (in_array($cfCountry, ['', 'XX', 'T1'], true)) {
            $cfCountry = '';
        }

        // Cache public IP geolocation lookup for 7 days
        return Cache::remember('ip_geo_'.$ip, now()->addDays(7), function () use ($ip, $cfCountry): array {
            try {
                $response = Http::timeout(1.2)->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,isp,org,as");

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status'] ?? '') === 'success') {
                        $isp = (string) ($data['isp'] ?? '');
                        $org = (string) ($data['org'] ?? ($data['as'] ?? ''));
                        $network = strtolower($isp.' '.$org);

                        return [
                            'is_local' => false,
                            'is_cloudflare' => ClientIp::isCloudflare($ip) || str_contains($network, 'cloudflare'),
                            'country' => (string) ($data['country'] ?? ($cfCountry === 'BD' ? 'Bangladesh' : ($cfCountry ?: 'Unknown'))),
                            'country_code' => (string) ($data['countryCode'] ?? ($cfCountry ?: '')),
                            'city' => (string) ($data['city'] ?? 'Unknown'),
                            'region' => (string) ($data['regionName'] ?? ''),
                            'isp' => $isp,
                            'org' => $org,
                        ];
                    }
                }
            } catch (\Throwable) {
                // Fallback gracefully on timeout or network unavailability
            }

            return [
                'is_local' => false,
                'is_cloudflare' => ClientIp::isCloudflare($ip),
                'country' => $cfCountry === 'BD' ? 'Bangladesh' : ($cfCountry ?: 'Unknown'),
                'country_code' => $cfCountry,
                'city' => 'Unknown',
                'region' => '',
                'isp' => '',
                'org' => '',
            ];
        });
    }

    /**
     * Check if an IP address belongs to RFC1918 / private address space.
     */
    public function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * Normalize cart items for audit log inspection.
     *
     * @param  iterable<int, array{product_id?: int|null, name?: string|null, product?: Product|null, quantity?: int|null, price?: float|int|null}|Product>  $products
     * @return array{items: array<int, array{id: int|null, name: string, quantity: int, price: float}>, count: int, total: float}
     */
    private function summarizeCartItems(iterable $products): array
    {
        $items = [];
        $totalCount = 0;
        $totalAmount = 0.0;

        foreach ($products as $item) {
            $id = null;
            $name = 'Product';
            $qty = 1;
            $price = 0.0;

            if ($item instanceof Product) {
                $id = (int) $item->id;
                $name = (string) $item->name;
                $price = (float) ($item->effective_price ?? $item->price ?? 0);
            } elseif (is_array($item)) {
                $id = $item['product_id'] ?? ($item['product']->id ?? null);
                $name = $item['name'] ?? ($item['product']->name ?? ('Product #'.($id ?? '')));
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['price'] ?? ($item['product']->effective_price ?? 0));
            } elseif (is_object($item)) {
                $id = $item->product_id ?? ($item->product->id ?? null);
                $name = $item->name ?? ($item->product->name ?? ('Product #'.($id ?? '')));
                $qty = (int) ($item->quantity ?? 1);
                $price = (float) ($item->price ?? ($item->product->effective_price ?? 0));
            }

            if ($id !== null || $name !== 'Product') {
                $items[] = [
                    'id' => $id,
                    'name' => (string) $name,
                    'quantity' => max(1, $qty),
                    'price' => $price,
                ];
                $totalCount += max(1, $qty);
                $totalAmount += ($price * max(1, $qty));
            }
        }

        return [
            'items' => $items,
            'count' => $totalCount,
            'total' => $totalAmount,
        ];
    }
}
