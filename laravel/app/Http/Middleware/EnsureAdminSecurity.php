<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\User;
use App\Services\AdminLoggerService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSecurity
{
    /**
     * Handle an incoming request with bank-grade admin security checks.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Authentication & Active Check
        /** @var User|Admin|null $user */
        $user = $request->user() ?? Auth::guard('admin')->user();

        if (! $user) {
            AdminLoggerService::log(
                action: 'FAILED_ADMIN_ACCESS',
                description: 'Unauthenticated attempt to access admin protected route: /'.ltrim($request->path(), '/'),
                statusCode: 401,
            );

            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }

            return redirect()->guest(route('admin.login'));
        }

        if (isset($user->is_active) && ! (bool) $user->is_active) {
            AdminLoggerService::log(
                action: 'DEACTIVATED_USER_ACCESS_BLOCKED',
                description: "Suspended/deactivated account (ID: {$user->getAuthIdentifier()}) attempted admin access.",
                model: $user,
                statusCode: 403,
            );

            Auth::logout();
            if (Auth::guard('admin')->check()) {
                Auth::guard('admin')->logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            abort(403, 'Your account has been suspended or deactivated.');
        }

        // 2. Role Authorization
        $allowedRoles = [];
        foreach ($roles as $roleSegment) {
            foreach (explode(',', $roleSegment) as $singleRole) {
                $trimmed = trim($singleRole);
                if ($trimmed !== '') {
                    $allowedRoles[] = $trimmed;
                }
            }
        }

        if (! empty($allowedRoles)) {
            $hasRole = false;

            if (method_exists($user, 'hasRole')) {
                $hasRole = $user->hasRole($allowedRoles);
            } elseif (isset($user->role)) {
                $hasRole = in_array($user->role, $allowedRoles, true);
            }

            if (! $hasRole) {
                AdminLoggerService::log(
                    action: 'UNAUTHORIZED_ADMIN_ATTEMPT',
                    description: "User (ID: {$user->getAuthIdentifier()}) unauthorized for route /".ltrim($request->path(), '/'),
                    model: $user,
                    customPayload: [
                        'required_roles' => $allowedRoles,
                        'user_role' => $user->role ?? null,
                    ],
                    statusCode: 404,
                );

                abort(404);
            }
        }

        // 3. Optional IP Whitelisting
        $allowedIps = config('admin.allowed_ips', []);
        if (is_string($allowedIps)) {
            $allowedIps = array_filter(array_map('trim', explode(',', $allowedIps)));
        }

        if (! empty($allowedIps) && is_array($allowedIps)) {
            $clientIp = (string) $request->ip();
            if (! in_array($clientIp, $allowedIps, true)) {
                AdminLoggerService::log(
                    action: 'BLOCKED_ADMIN_IP',
                    description: "Access denied by IP whitelisting for IP {$clientIp} on route /".ltrim($request->path(), '/'),
                    model: $user,
                    customPayload: [
                        'client_ip' => $clientIp,
                        'allowed_ips' => $allowedIps,
                    ],
                    statusCode: 404,
                );

                abort(404);
            }
        }

        // 4. Session Hijacking Protection (UA + IP binding)
        // DevTools mobile emulation changes User-Agent only — allow rebind when IP is unchanged.
        if ($request->hasSession()) {
            $currentUserAgent = (string) $request->userAgent();
            $currentUaHash = hash('sha256', $currentUserAgent);
            $currentIp = (string) $request->ip();

            if ($request->session()->has('admin_ua_hash')) {
                $storedUaHash = (string) $request->session()->get('admin_ua_hash');
                $storedIp = (string) $request->session()->get('admin_ip', '');

                if (! hash_equals($storedUaHash, $currentUaHash)) {
                    // Same IP + new UA = DevTools device mode / browser update — rebind.
                    // Different IP + new UA = treat as hijack.
                    $sameIp = $storedIp === '' || hash_equals($storedIp, $currentIp);

                    if ($sameIp) {
                        $request->session()->put('admin_ua_hash', $currentUaHash);
                        $request->session()->put('admin_ip', $currentIp);
                    } else {
                        AdminLoggerService::log(
                            action: 'SESSION_HIJACK_SUSPECTED',
                            description: "User-Agent + IP mismatch detected for user {$user->getAuthIdentifier()}. Session terminated.",
                            model: $user,
                            customPayload: [
                                'stored_hash' => $storedUaHash,
                                'current_hash' => $currentUaHash,
                                'stored_ip' => $storedIp,
                                'current_ip' => $currentIp,
                            ],
                            statusCode: 401,
                        );

                        Auth::logout();
                        if (Auth::guard('admin')->check()) {
                            Auth::guard('admin')->logout();
                        }

                        $request->session()->invalidate();
                        $request->session()->regenerateToken();

                        if ($request->expectsJson()) {
                            abort(401, 'Session invalid or hijacked.');
                        }

                        return redirect()->guest(route('admin.login'))->withErrors([
                            'security' => 'Session security violation detected. Please sign in again.',
                        ]);
                    }
                } else {
                    // Keep IP fingerprint fresh while UA stays stable.
                    $request->session()->put('admin_ip', $currentIp);
                }
            } else {
                $request->session()->put('admin_ua_hash', $currentUaHash);
                $request->session()->put('admin_ip', $currentIp);
            }
        }

        // Execute downstream middleware and controller action
        $response = $next($request);

        // 5. Automated State-Changing Request Logging
        if (in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $action = strtoupper($request->method()).' /'.ltrim($request->path(), '/');
            $statusCode = $response->getStatusCode();
            $sanitizedInput = AdminLoggerService::sanitizePayload($request->all());

            AdminLoggerService::log(
                action: $action,
                description: "Admin (ID: {$user->getAuthIdentifier()}) executed {$request->method()} on /".ltrim($request->path(), '/'),
                model: null,
                customPayload: [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'route_name' => $request->route()?->getName(),
                    'input' => $sanitizedInput,
                ],
                statusCode: $statusCode,
            );
        }

        return $response;
    }
}
