<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleAdminAjaxResponses
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (($request->ajax() || $request->wantsJson()) && ! ($response instanceof JsonResponse)) {
            if ($response instanceof RedirectResponse) {
                $session = $request->hasSession() ? $request->session() : null;
                $successMessage = $session ? ($session->get('success') ?? $session->get('status') ?? $session->get('message')) : null;
                $errorMessage = $session && $session->has('error') ? $session->get('error') : null;

                $targetUrl = $response->getTargetUrl();

                return response()->json([
                    'success' => ! $errorMessage && ! ($session && $session->has('errors')),
                    'message' => $errorMessage ?? $successMessage ?? 'Saved successfully.',
                    'redirect' => $targetUrl,
                ]);
            }
        }

        return $response;
    }
}
