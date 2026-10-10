<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\ChatMessage;
use App\Models\ContactMessage;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Support\AdminListing;
use App\Support\ReactPage;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $openLeads = ServiceRequest::whereNotIn('lead_stage', ['won', 'lost']);
        $stats = [
            'open_leads' => (clone $openLeads)->count(),
            'active_projects' => Project::whereNotIn('status', ['completed', 'cancelled'])->count(),
            'active_students' => CourseEnrollment::where('status', 'active')->count(),
            'published_content' => Service::where('is_active', true)->count() + Blog::where('is_published', true)->count(),
        ];
        $attention = [
            'overdue_follow_ups' => (clone $openLeads)->whereDate('follow_up_on', '<', today())->count(),
            'applications_to_review' => CourseEnrollment::where('status', 'applied')->count(),
            'payments_to_review' => CoursePayment::whereIn('status', ['pending', 'review_required'])->count(),
            'pending_messages' => ContactMessage::where('status', 'pending')->count(),
            'unread_chat_messages' => ChatMessage::where('is_admin', false)->where('is_read', false)->count(),
        ];
        $recentRequests = ServiceRequest::with(['service:id,title', 'user:id,name', 'assignee:id,name'])
            ->latest()->orderByDesc('id')->paginate(AdminListing::perPage($request, 10))->withQueryString();
        $dueProjects = Project::whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_on')->with('assignee:id,name')->orderBy('due_on')->orderBy('id')->limit(5)->get();
        $leadStages = ServiceRequest::selectRaw('lead_stage, count(*) as total')->groupBy('lead_stage')->pluck('total', 'lead_stage');

        return ReactPage::render('admin.dashboard', compact('stats', 'attention', 'recentRequests', 'dueProjects', 'leadStages'));
    }
}
