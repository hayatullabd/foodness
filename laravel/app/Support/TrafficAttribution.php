<?php

namespace App\Support;

use Illuminate\Http\Request;

class TrafficAttribution
{
    public const SESSION_KEY = 'traffic_attribution';

    /**
     * @var list<string>
     */
    private const UTM_KEYS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    /**
     * @var list<string>
     */
    private const CLICK_ID_KEYS = [
        'fbclid',
        'gclid',
        'ttclid',
    ];

    /**
     * Capture first-touch attribution in the session.
     * Existing values are kept unless the request brings new UTM or click-id params.
     */
    public function capture(Request $request): void
    {
        if ($request->is('admin') || $request->is('admin/*') || $request->is('up')) {
            return;
        }

        $incoming = $this->extractFromRequest($request);
        $existing = $this->fromSession();

        if ($existing === []) {
            session()->put(self::SESSION_KEY, $incoming);

            return;
        }

        if ($this->hasTrackingParams($incoming)) {
            session()->put(self::SESSION_KEY, $this->mergeWithNewTracking($existing, $incoming));
        }
    }

    /**
     * @return array{
     *     source_platform: string,
     *     landing_url: ?string,
     *     referrer: ?string,
     *     utm_source: ?string,
     *     utm_medium: ?string,
     *     utm_campaign: ?string,
     *     utm_content: ?string,
     *     utm_term: ?string,
     *     fbclid: ?string,
     *     gclid: ?string,
     *     ttclid: ?string,
     *     user_agent: ?string,
     *     attribution_json: array<string, mixed>
     * }
     */
    public function attributesForPersistence(): array
    {
        $data = $this->fromSession();

        if ($data === []) {
            $data = [
                'source_platform' => 'direct',
                'landing_url' => null,
                'referrer' => null,
                'utm_source' => null,
                'utm_medium' => null,
                'utm_campaign' => null,
                'utm_content' => null,
                'utm_term' => null,
                'fbclid' => null,
                'gclid' => null,
                'ttclid' => null,
                'user_agent' => null,
            ];
        }

        return [
            'source_platform' => $data['source_platform'] ?? 'direct',
            'landing_url' => $data['landing_url'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'fbclid' => $data['fbclid'] ?? null,
            'gclid' => $data['gclid'] ?? null,
            'ttclid' => $data['ttclid'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'attribution_json' => $data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromSession(): array
    {
        $data = session()->get(self::SESSION_KEY);

        return is_array($data) ? $data : [];
    }

    public function platformLabel(?string $platform): string
    {
        return OrderSources::label($platform);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFromRequest(Request $request): array
    {
        $utms = [];
        foreach (self::UTM_KEYS as $key) {
            $utms[$key] = $this->stringOrNull($request->query($key));
        }

        $clickIds = [];
        foreach (self::CLICK_ID_KEYS as $key) {
            $clickIds[$key] = $this->stringOrNull($request->query($key));
        }

        $landingUrl = $this->truncate($request->fullUrl(), 2048);
        $referrer = $this->truncate($this->stringOrNull($request->headers->get('referer')), 2048);
        $userAgent = $this->truncate($this->stringOrNull($request->userAgent()), 1024);
        $platform = $this->resolvePlatform($utms, $clickIds, $referrer);

        return [
            'source_platform' => $platform,
            'landing_url' => $landingUrl,
            'referrer' => $referrer,
            ...$utms,
            ...$clickIds,
            'user_agent' => $userAgent,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeWithNewTracking(array $existing, array $incoming): array
    {
        $merged = $existing;

        foreach ([...self::UTM_KEYS, ...self::CLICK_ID_KEYS] as $key) {
            if (! empty($incoming[$key])) {
                $merged[$key] = $incoming[$key];
            }
        }

        if (! empty($incoming['landing_url'])) {
            $merged['landing_url'] = $incoming['landing_url'];
        }

        if (! empty($incoming['referrer']) && empty($merged['referrer'])) {
            $merged['referrer'] = $incoming['referrer'];
        }

        if (! empty($incoming['user_agent']) && empty($merged['user_agent'])) {
            $merged['user_agent'] = $incoming['user_agent'];
        }

        $merged['source_platform'] = $this->resolvePlatform(
            [
                'utm_source' => $merged['utm_source'] ?? null,
                'utm_medium' => $merged['utm_medium'] ?? null,
                'utm_campaign' => $merged['utm_campaign'] ?? null,
                'utm_content' => $merged['utm_content'] ?? null,
                'utm_term' => $merged['utm_term'] ?? null,
            ],
            [
                'fbclid' => $merged['fbclid'] ?? null,
                'gclid' => $merged['gclid'] ?? null,
                'ttclid' => $merged['ttclid'] ?? null,
            ],
            is_string($merged['referrer'] ?? null) ? $merged['referrer'] : null,
        );

        $merged['updated_at'] = now()->toIso8601String();

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hasTrackingParams(array $data): bool
    {
        foreach ([...self::UTM_KEYS, ...self::CLICK_ID_KEYS] as $key) {
            if (! empty($data[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{utm_source?: ?string, utm_medium?: ?string, utm_campaign?: ?string, utm_content?: ?string, utm_term?: ?string}  $utms
     * @param  array{fbclid?: ?string, gclid?: ?string, ttclid?: ?string}  $clickIds
     */
    private function resolvePlatform(array $utms, array $clickIds, ?string $referrer): string
    {
        $source = strtolower((string) ($utms['utm_source'] ?? ''));

        if ($source !== '') {
            return $this->platformFromToken($source);
        }

        if (! empty($clickIds['fbclid'])) {
            return 'facebook';
        }

        if (! empty($clickIds['gclid'])) {
            return 'google';
        }

        if (! empty($clickIds['ttclid'])) {
            return 'tiktok';
        }

        if ($referrer === null || $referrer === '') {
            return 'direct';
        }

        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));

        if ($host === '') {
            return 'referral';
        }

        return $this->platformFromToken($host);
    }

    private function platformFromToken(string $token): string
    {
        $token = strtolower($token);

        if (str_contains($token, 'facebook') || str_contains($token, 'fb.') || $token === 'fb' || str_contains($token, 'meta')) {
            return 'facebook';
        }

        if (str_contains($token, 'instagram') || $token === 'ig') {
            return 'instagram';
        }

        if (str_contains($token, 'google') || str_contains($token, 'youtube') || str_contains($token, 'youtu.be') || $token === 'yt') {
            if (str_contains($token, 'youtube') || str_contains($token, 'youtu.be') || $token === 'yt') {
                return 'youtube';
            }

            return 'google';
        }

        if (str_contains($token, 'tiktok') || $token === 'tt') {
            return 'tiktok';
        }

        if (in_array($token, ['direct', '(direct)', 'none'], true)) {
            return 'direct';
        }

        return $token !== '' ? preg_replace('/[^a-z0-9_-]+/', '_', $token) ?: 'referral' : 'referral';
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function truncate(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }
}
