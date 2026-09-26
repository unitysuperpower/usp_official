<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ProjectWorkspace
{
    public static function summary(Project $project, bool $admin = false): array
    {
        $data = $project->only(['id', 'title', 'description', 'status', 'due_on', 'completed_at', 'created_at', 'customer_name']);
        $total = (int) $project->milestones_count;
        $complete = (int) $project->completed_milestones_count;
        $data += ['milestones_count' => $total, 'completed_milestones_count' => $complete, 'progress' => $total ? (int) round($complete / $total * 100) : 0, 'assignee' => $project->assignee?->only(['id', 'name'])];
        if ($admin) {
            $data += $project->only(['customer_id', 'customer_email', 'assigned_to', 'internal_notes', 'service_request_id']);
        }

        return $data;
    }

    public static function props(Project $project, bool $admin): array
    {
        $project->load('assignee:id,name')->loadCount(['milestones', 'milestones as completed_milestones_count' => fn ($q) => $q->where('status', 'completed')]);
        $updates = $project->updates()->when(! $admin, fn ($q) => $q->where('is_internal', false))->with('author:id,name')->latest('id')->paginate(15, ['*'], 'updates_page')->withQueryString();
        $files = $project->files()->latest('id')->paginate(10, ['id', 'project_id', 'uploaded_by', 'original_name', 'size', 'created_at'], 'files_page')->withQueryString();
        $props = ['project' => self::summary($project, $admin), 'milestones' => $project->milestones()->orderBy('id')->get(), 'updates' => $updates, 'files' => $files];
        if ($admin) {
            $props['assignees'] = User::where('is_admin', true)->orderBy('name')->get(['id', 'name']);
            $props['linkedCustomerEmail'] = $project->customer?->email;
        }

        return $props;
    }

    public static function requireOpen(Project $project): void
    {
        if (in_array($project->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['project' => 'Reopen this project before changing its milestones.']);
        }
    }
}
