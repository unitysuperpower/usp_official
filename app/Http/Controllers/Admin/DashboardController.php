<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Support\ReactPage;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_services' => Service::count(),
            'active_projects' => Project::whereNotIn('status', ['completed', 'cancelled'])->count(),
            'total_requests' => ServiceRequest::count(),
            'pending_requests' => ServiceRequest::where('status', 'pending')->count(),
            'overdue_follow_ups' => ServiceRequest::whereNotIn('lead_stage', ['won', 'lost'])->whereDate('follow_up_on', '<', today())->count(),
        ];

        $recentRequests = ServiceRequest::with(['service.category', 'user'])
            ->latest()
            ->take(10)
            ->get();

        return ReactPage::render('admin.dashboard', compact('stats', 'recentRequests'));
    }
}
