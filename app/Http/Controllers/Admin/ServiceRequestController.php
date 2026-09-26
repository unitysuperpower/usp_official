<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:200', 'stage' => ['nullable', Rule::in(ServiceRequest::STAGES)], 'assigned_to' => 'nullable|integer', 'overdue' => 'nullable|boolean']);
        $query = ServiceRequest::with(['service:id,title', 'assignee:id,name', 'project:id,service_request_id,title']);
        if ($search = $filters['search'] ?? null) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('company', 'like', "%{$search}%"));
        }
        $query->when($filters['stage'] ?? null, fn ($q, $stage) => $q->where('lead_stage', $stage));
        $query->when($filters['assigned_to'] ?? null, fn ($q, $id) => $q->where('assigned_to', $id));
        $query->when($request->boolean('overdue'), fn ($q) => $q->whereNotIn('lead_stage', ['won', 'lost'])->whereDate('follow_up_on', '<', today()));
        $requests = $query->latest()->paginate(20)->withQueryString();
        $stats = [
            'open_leads' => ServiceRequest::whereNotIn('lead_stage', ['won', 'lost'])->count(),
            'qualified' => ServiceRequest::where('lead_stage', 'qualified')->count(),
            'overdue_follow_ups' => ServiceRequest::whereNotIn('lead_stage', ['won', 'lost'])->whereDate('follow_up_on', '<', today())->count(),
            'won' => ServiceRequest::where('lead_stage', 'won')->count(),
        ];
        $assignees = User::where('is_admin', true)->orderBy('name')->get(['id', 'name']);

        return ReactPage::render('admin.requests.index', compact('requests', 'stats', 'assignees', 'filters'));
    }

    public function show(ServiceRequest $request)
    {
        $request->load(['service', 'user', 'assignee:id,name', 'project:id,service_request_id,title']);
        $assignees = User::where('is_admin', true)->orderBy('name')->get(['id', 'name']);

        return ReactPage::render('admin.requests.show', compact('request', 'assignees'));
    }

    public function updateLead(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'lead_stage' => ['required', Rule::in(ServiceRequest::STAGES)],
            'priority' => 'required|in:low,normal,high',
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_admin', true)],
            'follow_up_on' => 'nullable|date_format:Y-m-d',
            'admin_notes' => 'nullable|string|max:20000',
        ]);
        DB::transaction(function () use ($serviceRequest, $data) {
            $lead = ServiceRequest::whereKey($serviceRequest->id)->lockForUpdate()->firstOrFail();
            if ($lead->project()->exists() && $data['lead_stage'] !== 'won') {
                throw ValidationException::withMessages(['lead_stage' => 'This lead has a project. Manage delivery from that project.']);
            }
            $lead->update($data);
        });

        return back()->with('success', 'Lead updated.');
    }

    public function convert(Request $request, ServiceRequest $serviceRequest)
    {
        // Retried submissions always resolve to the existing project.
        if ($project = $serviceRequest->project) {
            return redirect()->route('admin.projects.show', $project);
        }
        $data = $request->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:20000', 'due_on' => 'nullable|date_format:Y-m-d']);
        $project = DB::transaction(function () use ($serviceRequest, $data) {
            $lead = ServiceRequest::whereKey($serviceRequest->id)->lockForUpdate()->firstOrFail();
            if ($existing = $lead->project()->first()) {
                return $existing;
            }
            if ($lead->lead_stage === 'lost') {
                throw ValidationException::withMessages(['lead_stage' => 'Reopen this lost lead before creating a project.']);
            }
            $project = Project::create($data + [
                'service_request_id' => $lead->id,
                'customer_id' => $lead->user && ! $lead->user->is_admin ? $lead->user_id : null,
                'assigned_to' => $lead->assigned_to,
                'customer_name' => $lead->name,
                'customer_email' => $lead->email,
                'status' => 'planning',
            ]);
            $lead->update(['lead_stage' => 'won', 'status' => 'in_progress', 'follow_up_on' => null]);

            return $project;
        });

        return redirect()->route('admin.projects.show', $project)->with('success', 'Project created from this lead.');
    }

    public function updateStatus(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate(['status' => 'required|in:pending,in_progress,completed,cancelled', 'admin_notes' => 'nullable|string|max:20000']);
        if ($serviceRequest->project()->exists()) {
            throw ValidationException::withMessages(['status' => 'Manage delivery status from the linked project.']);
        }
        $serviceRequest->update($validated);

        return back()->with('success', 'Request status updated successfully.');
    }
}
