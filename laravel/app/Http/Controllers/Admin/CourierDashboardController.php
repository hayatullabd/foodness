<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Courier\CourierDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierDashboardController extends Controller
{
    public function index(Request $request, CourierDashboardService $dashboard): View
    {
        $payload = $dashboard->build($request->boolean('refresh'));

        return view('admin.courier-panel.index', [
            'panels' => $payload['panels'],
            'summary' => $payload['summary'],
            'recentBookings' => $payload['recent_bookings'],
            'refreshed' => $request->boolean('refresh'),
        ]);
    }
}
