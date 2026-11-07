<?php

namespace App\Http\Middleware;

use App\Support\TrafficAttribution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureTrafficAttribution
{
    public function __construct(private TrafficAttribution $trafficAttribution) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->trafficAttribution->capture($request);

        return $next($request);
    }
}
