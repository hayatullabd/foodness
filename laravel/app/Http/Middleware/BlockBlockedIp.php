<?php

namespace App\Http\Middleware;

use App\Models\IpBlock;
use App\Models\OrderBlockLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockBlockedIp
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin') || $request->is('admin/*') || $request->is('up')) {
            return $next($request);
        }

        $ip = (string) $request->ip();

        if ($ip !== '' && IpBlock::isBlocked($ip)) {
            try {
                OrderBlockLog::record(
                    blockType: 'ip_blacklist',
                    reason: 'Access from this IP address is blocked by security policy.',
                    offendingValue: $ip,
                    ipAddress: $ip,
                );
            } catch (\Throwable) {
                // Ignore logging failure
            }

            abort(403, 'Access from this IP address is blocked.');
        }

        return $next($request);
    }
}
