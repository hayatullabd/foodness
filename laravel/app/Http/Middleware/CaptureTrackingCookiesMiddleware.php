<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureTrackingCookiesMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip administrative and internal health check routes
        if ($request->is('admin') || $request->is('admin/*') || $request->is('up')) {
            return $next($request);
        }

        $nowMs = (int) round(microtime(true) * 1000);

        // 1. Capture & Format Facebook Click ID (fbclid) -> _fbc cookie
        $rawFbclid = $request->query('fbclid');
        $fbcCookie = $request->cookie('_fbc') ?? $request->cookies->get('_fbc');

        if (filled($rawFbclid)) {
            $safeFbclid = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $rawFbclid);
            if (filled($safeFbclid)) {
                $fbcCookie = sprintf('fb.1.%d.%s', $nowMs, $safeFbclid);
                // Queue for 90 days (129,600 minutes), httpOnly = false so Meta Pixel client JS can read it
                cookie()->queue(cookie()->make('_fbc', $fbcCookie, 60 * 24 * 90, '/', null, $request->isSecure(), false, false, 'Lax'));
            }
        }

        // 2. Capture TikTok Click ID (ttclid) -> ttclid cookie
        $rawTtclid = $request->query('ttclid');
        $ttclidCookie = $request->cookie('ttclid') ?? $request->cookies->get('ttclid');

        if (filled($rawTtclid)) {
            $safeTtclid = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $rawTtclid);
            if (filled($safeTtclid)) {
                $ttclidCookie = $safeTtclid;
                cookie()->queue(cookie()->make('ttclid', $ttclidCookie, 60 * 24 * 90, '/', null, $request->isSecure(), false, false, 'Lax'));
            }
        }

        // 3. Extract browser cookies (set by client tags)
        $fbpCookie = $request->cookie('_fbp') ?? $request->cookies->get('_fbp');
        $ttpCookie = $request->cookie('_ttp') ?? $request->cookies->get('_ttp');
        $gaCookie = $request->cookie('_ga') ?? $request->cookies->get('_ga');

        // Extract GA4 Client ID (e.g., GA1.1.123456789.1620000000 -> 123456789.1620000000)
        $gaClientId = null;
        if (filled($gaCookie) && preg_match('/(?:GA\d\.\d\.)?([0-9]+\.[0-9]+)/', (string) $gaCookie, $matches)) {
            $gaClientId = $matches[1];
        }

        // 4. Assemble reliable tracking context
        $context = [
            'fbc' => $fbcCookie,
            'fbp' => $fbpCookie,
            'ttp' => $ttpCookie,
            'ttclid' => $ttclidCookie,
            'ga' => $gaCookie,
            'ga_client_id' => $gaClientId,
            'fbclid' => filled($rawFbclid) ? (string) $rawFbclid : null,
            'client_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'source_url' => $request->fullUrl(),
            'referrer' => $request->headers->get('referer'),
            'captured_at' => now()->toIso8601String(),
        ];

        // Store into current request attributes and session for downstream background jobs & controllers
        $request->attributes->set('tracking_context', $context);

        // Keep or merge session cookies
        $existingSession = session()->get('tracking_cookies', []);
        if (! is_array($existingSession)) {
            $existingSession = [];
        }

        $mergedSession = array_filter([
            ...$existingSession,
            ...array_filter($context, fn ($v) => $v !== null && $v !== ''),
        ]);

        session()->put('tracking_cookies', $mergedSession);

        return $next($request);
    }
}
