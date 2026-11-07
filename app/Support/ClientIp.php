<?php

namespace App\Support;

use Illuminate\Http\Request;

class ClientIp
{
    /**
     * Cloudflare edge ranges. Updated from https://www.cloudflare.com/ips-v4 and ips-v6.
     *
     * @var list<string>
     */
    private const CLOUDFLARE_V4 = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
    ];

    /**
     * @var list<string>
     */
    private const CLOUDFLARE_V6 = [
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    public static function resolve(Request $request): string
    {
        $remote = trim((string) $request->server->get('REMOTE_ADDR'));
        $connecting = self::firstPublicIp($request->headers->get('CF-Connecting-IP'));

        if ($connecting !== null && self::requestCameThroughCloudflare($request, $remote)) {
            return $connecting;
        }

        $trueClient = self::firstPublicIp($request->headers->get('True-Client-IP'));
        if ($trueClient !== null && self::requestCameThroughCloudflare($request, $remote)) {
            return $trueClient;
        }

        if (self::isTrustedProxy($remote)) {
            $forwarded = self::publicFromForwarded($request->headers->get('X-Forwarded-For'));
            if ($forwarded !== null) {
                return $forwarded;
            }

            $real = self::firstPublicIp($request->headers->get('X-Real-IP'));
            if ($real !== null) {
                return $real;
            }
        }

        return $remote;
    }

    private static function isTrustedProxy(string $ip): bool
    {
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $trusted = array_filter(array_map(
            trim(...),
            explode(',', (string) config('app.trusted_proxies', '')),
        ));

        return in_array($ip, $trusted, true);
    }

    public static function apply(Request $request): void
    {
        $ip = self::resolve($request);

        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return;
        }

        $request->server->set('REMOTE_ADDR', $ip);
    }

    /**
     * Location headers Cloudflare can attach for the real visitor.
     * City and region are present only when "Add visitor location headers" is enabled.
     *
     * @return array{ip: string, country: ?string, city: ?string, region: ?string, timezone: ?string, latitude: ?string, longitude: ?string}
     */
    public static function locationSnapshot(Request $request, ?string $ip = null): array
    {
        $country = strtoupper((string) self::headerValue($request, 'CF-IPCountry'));
        if (in_array($country, ['', 'XX', 'T1'], true)) {
            $country = null;
        }

        return [
            'ip' => $ip ?? self::resolve($request),
            'country' => $country,
            'city' => self::decodedHeader($request, 'CF-IPCity'),
            'region' => self::decodedHeader($request, 'CF-Region'),
            'timezone' => self::headerValue($request, 'CF-Timezone'),
            'latitude' => self::headerValue($request, 'CF-IPLatitude'),
            'longitude' => self::headerValue($request, 'CF-IPLongitude'),
        ];
    }

    public static function isCloudflare(?string $ip): bool
    {
        $ip = trim((string) $ip);
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long === false) {
                return false;
            }

            foreach (self::CLOUDFLARE_V4 as $cidr) {
                if (self::ipv4InCidr($long, $cidr)) {
                    return true;
                }
            }

            return false;
        }

        $packed = @inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        foreach (self::CLOUDFLARE_V6 as $cidr) {
            if (self::ipv6InCidr($packed, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private static function requestCameThroughCloudflare(Request $request, string $remote): bool
    {
        if (self::isCloudflare($remote)) {
            return true;
        }

        $hasCloudflareMark = $request->headers->has('CF-Ray') || $request->headers->has('CF-Visitor');

        return $hasCloudflareMark && ($remote === '' || self::isPrivateOrReserved($remote));
    }

    private static function publicFromForwarded(?string $header): ?string
    {
        if ($header === null || trim($header) === '') {
            return null;
        }

        $parts = array_reverse(array_map(trim(...), explode(',', $header)));
        $fallback = null;

        foreach ($parts as $part) {
            $ip = self::firstPublicIp($part);
            if ($ip === null) {
                continue;
            }

            if (! self::isCloudflare($ip)) {
                return $ip;
            }

            $fallback ??= $ip;
        }

        return $fallback;
    }

    private static function firstPublicIp(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        foreach (explode(',', $value) as $part) {
            $ip = trim($part);
            if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
                continue;
            }

            if (! self::isPrivateOrReserved($ip)) {
                return $ip;
            }
        }

        return null;
    }

    private static function isPrivateOrReserved(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    private static function ipv4InCidr(int $ipLong, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $mask = -1 << (32 - (int) $bits);
        $network = ip2long($subnet);

        if ($network === false) {
            return false;
        }

        return ($ipLong & $mask) === ($network & $mask);
    }

    private static function ipv6InCidr(string $packed, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $network = @inet_pton($subnet);
        if ($network === false) {
            return false;
        }

        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($bytes > 0 && substr($packed, 0, $bytes) !== substr($network, 0, $bytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainder)) & 0xFF;

        return (ord($packed[$bytes]) & $mask) === (ord($network[$bytes]) & $mask);
    }

    private static function headerValue(Request $request, string $name): ?string
    {
        $value = trim((string) $request->headers->get($name));

        return $value === '' ? null : $value;
    }

    private static function decodedHeader(Request $request, string $name): ?string
    {
        $value = self::headerValue($request, $name);
        if ($value === null) {
            return null;
        }

        $decoded = rawurldecode($value);

        return trim($decoded) === '' ? null : trim($decoded);
    }
}
