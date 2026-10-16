<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Support\AdminListing;
use App\Support\ProjectWorkspace;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:200', 'status' => ['nullable', Rule::in(Project::STATUSES)], 'assigned_to' => 'nullable|integer', 'overdue' => 'nullable|boolean']);
        $query = Project::with('assignee:id,name')->withCount(['milestones', 'milestones as completed_milestones_count' => fn ($q) => $q->where('status', 'completed')]);
        if ($search = $filters['search'] ?? null) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%"));
        }
        $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
        $query->when($filters['assigned_to'] ?? null, fn ($q, $id) => $q->where('assigned_to', $id));
        $query->when($request->boolean('overdue'), fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled'])->whereDate('due_on', '<', today()));
        $projects = $query->latest()->paginate(AdminListing::perPage($request))->withQueryString();
        $projects->setCollection($projects->getCollection()->map(fn ($project) => ProjectWorkspace::summary($project, true)));
        $assignees = User::where('is_admin', true)->orderBy('name')->get(['id', 'name']);
        $stats = ['active_projects' => Project::whereIn('status', ['planning', 'active', 'on_hold'])->count(), 'overdue_projects' => Project::whereNotIn('status', ['completed', 'cancelled'])->whereDate('due_on', '<', today())->count(), 'awaiting_approval' => ProjectMilestone::where('status', 'awaiting_approval')->whereHas('project', fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']))->count(), 'completed_projects' => Project::where('status', 'completed')->count()];

        return ReactPage::render('admin.projects.index', compact('projects', 'assignees', 'stats', 'filters'));
    }

    public function show(Project $project)
    {
        return ReactPage::render('admin.projects.show', ProjectWorkspace::props($project, true));
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255', 'description' => 'nullable|string|max:20000',
            'status' => ['required', Rule::in(Project::STATUSES)], 'due_on' => 'nullable|date_format:Y-m-d',
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_admin', true)],
            'internal_notes' => 'nullable|string|max:20000',
            'customer_account_email' => ['nullable', 'email', Rule::exists('users', 'email')->where('is_admin', 0)],
        ]);
        DB::transaction(function () use ($project, $data, $request) {
            $project = Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
            if ($data['status'] === 'completed' && $project->milestones()->where('status', '!=', 'completed')->exists()) {
                throw ValidationException::withMessages(['status' => 'Complete all milestones and customer approvals before completing the project.']);
            }
            if ($request->has('customer_account_email')) {
                $data['customer_id'] = ! empty($data['customer_account_email']) ? User::where('email', $data['customer_account_email'])->where('is_admin', false)->value('id') : null;
                if ($data['customer_id'] && $data['customer_id'] !== $project->customer_id) {
                    \App\Support\SiteNotifications::projectShared($project->forceFill(['customer_id' => $data['customer_id']]));
                }
            }
            unset($data['customer_account_email']);
            $data['completed_at'] = $data['status'] === 'completed' ? ($project->completed_at ?? now()) : null;
            $project->update($data);
            $project->serviceRequest?->update(['status' => match ($data['status']) {
                'completed' => 'completed', 'cancelled' => 'cancelled', default => 'in_progress'
            }]);
        });

        return back()->with('success', 'Project updated.');
    }

    public function storeMilestone(Request $request, Project $project)
    {
        $data = $this->milestoneData($request);
        DB::transaction(function () use ($project, $data) {
            $project = Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
            ProjectWorkspace::requireOpen($project);
            $this->validateApproval($data);
            $item = $project->milestones()->create($data);
            if ($item->status === 'awaiting_approval') {
                \App\Support\SiteNotifications::milestoneReview($project, $item);
            }
        });

        return back()->with('success', 'Milestone added.');
    }

    /** Share the project with a registered customer account so the client can see it. */
    public function linkCustomer(Request $request, Project $project)
    {
        $data = $request->validate(['customer_account_email' => ['required', 'email', Rule::exists('users', 'email')->where('is_admin', 0)]],
            ['customer_account_email.exists' => 'No customer account uses this email. Ask the client to register with it first, then link it here.']);
        DB::transaction(function () use ($project, $data) {
            $project = Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
            $customerId = User::where('email', $data['customer_account_email'])->where('is_admin', false)->value('id');
            if ($project->customer_id !== $customerId) {
                $project->update(['customer_id' => $customerId]);
                \App\Support\SiteNotifications::projectShared($project);
            }
        });

        return back()->with('success', 'Project shared. The client can now see it under My projects.');
    }

    /** Ask the client to approve a milestone. */
    public function requestApproval(Project $project, int $milestone)
    {
        DB::transaction(function () use ($project, $milestone) {
            $project = Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
            ProjectWorkspace::requireOpen($project);
            if (! $project->customer_id) {
                throw ValidationException::withMessages(['project' => 'Link the client\'s account first, so they can see and approve this milestone.']);
            }
            $item = $project->milestones()->whereKey($milestone)->lockForUpdate()->firstOrFail();
            if ($item->status === 'completed') {
                throw ValidationException::withMessages(['status' => 'This milestone is already completed.']);
            }
            $item->update(['requires_approval' => true, 'status' => 'awaiting_approval', 'approved_at' => null, 'approved_by' => null]);
            \App\Support\SiteNotifications::milestoneReview($project, $item);
        });

        return back()->with('success', 'Sent to the client for approval. They have been emailed and will see the approve button in their project.');
    }

    public function updateMilestone(Request $request, Project $project, int $milestone)
    {
        $data = $this->milestoneData($request);
        DB::transaction(function () use ($project, $milestone, $data) {
            $project = Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
            ProjectWorkspace::requireOpen($project);
            $item = $project->milestones()->whereKey($milestone)->lockForUpdate()->firstOrFail();
            // Editing approved work requires a fresh approval; unchanged saves preserve it.
            $wasAwaiting = $item->status === 'awaiting_approval';
            $item->fill($data);
            if ($item->isDirty()) {
                $this->validateApproval($data);
                $item->approved_at = null;
                $item->approved_by = null;
                $item->approval_note = null;
            }
            $item->save();
            if (! $wasAwaiting && $item->status === 'awaiting_approval' && $project->customer_id) {
                \App\Support\SiteNotifications::milestoneReview($project, $item);
            }
        });

        return back()->with('success', 'Milestone updated.');
    }

    private function milestoneData(Request $request): array
    {
        $data = $request->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'due_on' => 'nullable|date_format:Y-m-d', 'status' => ['required', Rule::in(['pending', 'in_progress', 'awaiting_approval', 'completed'])], 'requires_approval' => 'required|boolean']);
        $data['requires_approval'] = $request->boolean('requires_approval');

        return $data;
    }

    private function validateApproval(array $data): void
    {
        if ($data['requires_approval'] && $data['status'] === 'completed') {
            throw ValidationException::withMessages(['status' => 'Use awaiting approval so the customer can approve this milestone.']);
        }
        if (! $data['requires_approval'] && $data['status'] === 'awaiting_approval') {
            throw ValidationException::withMessages(['requires_approval' => 'Enable customer approval before requesting a review.']);
        }
    }
}
