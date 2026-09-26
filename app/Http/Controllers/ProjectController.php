<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectWorkspace;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    private function authorizeProject(Request $request, Project $project): void
    {
        abort_unless($request->user()->is_admin || $project->customer_id === $request->user()->id, 404);
    }

    public function index(Request $request)
    {
        $projects = Project::where('customer_id', $request->user()->id)->with('assignee:id,name')->withCount(['milestones', 'milestones as completed_milestones_count' => fn ($q) => $q->where('status', 'completed')])->latest()->paginate(15);
        $projects->setCollection($projects->getCollection()->map(fn ($project) => ProjectWorkspace::summary($project)));

        return ReactPage::render('projects.index', compact('projects'));
    }

    public function show(Request $request, Project $project)
    {
        abort_unless($project->customer_id === $request->user()->id, 404);

        return ReactPage::render('projects.show', ProjectWorkspace::props($project, false));
    }

    public function approval(Request $request, Project $project, int $milestone)
    {
        $data = $request->validate(['decision' => 'required|in:approve,request_changes', 'approval_note' => 'nullable|required_if:decision,request_changes|string|max:5000']);
        DB::transaction(function () use ($request, $project, $milestone, $data) {
            $project = Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
            abort_unless(! $request->user()->is_admin && $project->customer_id === $request->user()->id, 404);
            ProjectWorkspace::requireOpen($project);
            $item = $project->milestones()->whereKey($milestone)->lockForUpdate()->firstOrFail();
            if (! $item->requires_approval || $item->status !== 'awaiting_approval') {
                throw ValidationException::withMessages(['decision' => 'This milestone is no longer awaiting approval. Refresh the page.']);
            }
            $approved = $data['decision'] === 'approve';
            $item->update(['status' => $approved ? 'completed' : 'changes_requested', 'approved_at' => $approved ? now() : null, 'approved_by' => $approved ? $request->user()->id : null, 'approval_note' => $data['approval_note'] ?? null]);
            $project->updates()->create(['user_id' => $request->user()->id, 'body' => ($approved ? 'Approved milestone: ' : 'Requested changes to milestone: ').$item->title, 'is_internal' => false]);
        });

        return back()->with('success', 'Your milestone review has been saved.');
    }

    public function storeUpdate(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        $data = $request->validate(['body' => 'required|string|max:10000', 'is_internal' => 'nullable|boolean']);
        $project->updates()->create(['user_id' => $request->user()->id, 'body' => $data['body'], 'is_internal' => $request->user()->is_admin && $request->boolean('is_internal')]);

        return back()->with('success', 'Update posted.');
    }

    public function upload(Request $request, Project $project)
    {
        $this->authorizeProject($request, $project);
        $request->validate(['file' => 'required|file|mimes:pdf,txt,csv,jpg,jpeg,png,webp,docx,xlsx,zip|max:10240']);
        $file = $request->file('file');
        $path = $file->store('project-files/'.$project->id, 'local');
        abort_unless($path, 503, 'File storage is unavailable. Please try again.');
        try {
            $project->files()->create(['uploaded_by' => $request->user()->id, 'original_name' => mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $file->getClientOriginalName()))), 0, 240), 'path' => $path, 'size' => $file->getSize()]);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        return back()->with('success', 'File uploaded. Shared files are visible to the project customer.');
    }

    public function download(Request $request, Project $project, int $file)
    {
        $this->authorizeProject($request, $project);
        $item = $project->files()->findOrFail($file);
        abort_unless(Storage::disk('local')->exists($item->path), 404);

        return Storage::disk('local')->download($item->path, $item->original_name, ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function deleteFile(Request $request, Project $project, int $file)
    {
        $this->authorizeProject($request, $project);
        $item = $project->files()->findOrFail($file);
        abort_unless($request->user()->is_admin || $item->uploaded_by === $request->user()->id, 403);
        abort_unless(Storage::disk('local')->delete($item->path), 503, 'Could not remove the stored file. Please try again.');
        $item->delete();

        return back()->with('success', 'File removed.');
    }
}
