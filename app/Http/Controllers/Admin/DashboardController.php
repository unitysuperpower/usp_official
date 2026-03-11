<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_services' => Service::count(),
            'total_categories' => ServiceCategory::count(),
            'total_requests' => ServiceRequest::count(),
            'pending_requests' => ServiceRequest::where('status', 'pending')->count(),
            'total_users' => User::count(),
        ];

        $recentRequests = ServiceRequest::with(['service.category', 'user'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentRequests'));
    }
}
