<?php

namespace App\Http\Middleware;

use App\Support\ClientIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseRealClientIp
{
    /**
     * Replace the proxy address with the visitor IP before anything calls Request::ip().
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        ClientIp::apply($request);

        return $next($request);
    }
}
