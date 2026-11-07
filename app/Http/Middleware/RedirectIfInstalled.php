<?php

namespace App\Http\Middleware;

use App\Support\InstallationHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallationHelper::isInstalled()) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
