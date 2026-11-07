<?php

namespace App\Http\Middleware;

use App\Support\InstallationHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! InstallationHelper::isInstalled() && ! $request->is('install*')) {
            return redirect()->route('install.step1');
        }

        return $next($request);
    }
}
