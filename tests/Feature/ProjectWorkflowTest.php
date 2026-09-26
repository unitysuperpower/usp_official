<?php

use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->customer = User::factory()->create();
    $category = ServiceCategory::create(['name' => 'Web', 'slug' => 'web']);
    $service = Service::create(['category_id' => $category->id, 'title' => 'Website', 'slug' => 'website', 'description' => 'Build a site', 'price' => 100, 'price_unit' => 'project']);
    $this->lead = ServiceRequest::create(['service_id' => $service->id, 'user_id' => $this->customer->id, 'name' => $this->customer->name, 'email' => $this->customer->email, 'message' => 'Build my website']);
});

function workflowProject(ServiceRequest $lead, array $extra = []): Project
{
    return Project::create($extra + ['service_request_id' => $lead->id, 'customer_id' => $lead->user_id, 'customer_name' => $lead->name, 'customer_email' => $lead->email, 'title' => 'Website project', 'status' => 'active']);
}

function workflowMilestoneData(array $extra = []): array
{
    return $extra + ['title' => 'Design review', 'description' => 'Approve the homepage', 'status' => 'awaiting_approval', 'requires_approval' => true];
}

test('lead owners must be admins and overdue filters exclude closed leads', function () {
    $this->actingAs($this->admin)->patch('/admin/requests/'.$this->lead->id.'/lead', ['lead_stage' => 'qualified', 'priority' => 'high', 'assigned_to' => $this->customer->id])->assertSessionHasErrors('assigned_to');
    $this->patch('/admin/requests/'.$this->lead->id.'/lead', ['lead_stage' => 'qualified', 'priority' => 'high', 'assigned_to' => $this->admin->id, 'follow_up_on' => today()->subDay()->toDateString(), 'admin_notes' => 'Confidential lead notes'])->assertSessionHasNoErrors();
    $this->get('/admin/requests?overdue=1')->assertOk()->assertViewHas('props', fn ($p) => $p['requests']['total'] === 1 && $p['stats']['overdue_follow_ups'] === 1);
    $this->lead->update(['lead_stage' => 'lost']);
    $this->get('/admin/requests?overdue=1')->assertViewHas('props', fn ($p) => $p['requests']['total'] === 0);
    $this->actingAs($this->customer)->get('/dashboard')->assertDontSee('Confidential lead notes');
});

test('conversion is idempotent and preserves linked customer ownership', function () {
    $this->lead->update(['assigned_to' => $this->admin->id, 'follow_up_on' => today()]);
    $url = '/admin/requests/'.$this->lead->id.'/project';
    $this->actingAs($this->admin)->post($url, ['title' => 'Customer website', 'description' => 'Agreed scope'])->assertRedirect();
    $project = Project::sole();
    expect($project->customer_id)->toBe($this->customer->id);
    expect($project->assigned_to)->toBe($this->admin->id);
    expect($this->lead->refresh()->lead_stage)->toBe('won');
    expect($this->lead->follow_up_on)->toBeNull();
    $this->post($url, [])->assertRedirect('/admin/projects/'.$project->id);
    $this->assertDatabaseCount('projects', 1);
    $this->patch('/admin/requests/'.$this->lead->id.'/lead', ['lead_stage' => 'lost', 'priority' => 'normal'])->assertSessionHasErrors('lead_stage');
    $this->patch('/admin/requests/'.$this->lead->id.'/status', ['status' => 'completed'])->assertSessionHasErrors('status');
});

test('guest inquiry email does not grant account access without explicit linking', function () {
    $this->lead->update(['user_id' => null]);
    $this->actingAs($this->admin)->post('/admin/requests/'.$this->lead->id.'/project', ['title' => 'Guest project'])->assertRedirect();
    $project = Project::sole();
    expect($project->customer_id)->toBeNull();
    $this->actingAs($this->customer)->get('/projects/'.$project->id)->assertNotFound();
    $this->actingAs($this->admin)->patch('/admin/projects/'.$project->id, ['title' => $project->title, 'status' => 'active', 'customer_account_email' => $this->customer->email])->assertSessionHasNoErrors();
    $this->actingAs($this->customer)->get('/projects/'.$project->id)->assertOk();
});

test('customer pages exclude internal notes and updates and other customers projects', function () {
    $project = workflowProject($this->lead, ['internal_notes' => 'PRIVATE INTERNAL STRATEGY']);
    $project->updates()->create(['body' => 'PRIVATE UPDATE', 'is_internal' => true, 'user_id' => $this->admin->id]);
    $project->updates()->create(['body' => 'Shared progress', 'is_internal' => false, 'user_id' => $this->admin->id]);
    $this->actingAs($this->customer)->get('/projects/'.$project->id)->assertOk()->assertDontSee('PRIVATE INTERNAL STRATEGY')->assertDontSee('PRIVATE UPDATE')->assertSee('Shared progress')->assertViewHas('props', fn ($p) => ! array_key_exists('internal_notes', $p['project']) && $p['updates']['total'] === 1);
    $this->get('/projects')->assertDontSee('PRIVATE INTERNAL STRATEGY');
    $this->post('/projects/'.$project->id.'/updates', ['body' => 'Customer update', 'is_internal' => '1'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('project_updates', ['body' => 'Customer update', 'is_internal' => false]);
    $this->actingAs(User::factory()->create())->get('/projects/'.$project->id)->assertNotFound();
    $this->post('/projects/'.$project->id.'/updates', ['body' => 'Intruder'])->assertNotFound();
    $this->get('/projects')->assertViewHas('props', fn ($p) => $p['projects']['total'] === 0);
});

test('non admins cannot manage leads projects or milestones', function () {
    $project = workflowProject($this->lead);
    $this->actingAs($this->customer)->get('/admin/projects')->assertForbidden();
    $this->get('/admin/requests')->assertForbidden();
    $this->post('/admin/requests/'.$this->lead->id.'/project', ['title' => 'Unauthorized'])->assertForbidden();
    $this->patch('/admin/projects/'.$project->id, ['title' => 'Unauthorized', 'status' => 'completed'])->assertForbidden();
    $this->post('/admin/projects/'.$project->id.'/milestones', workflowMilestoneData())->assertForbidden();
});

test('customer approval completes milestones and permits project completion', function () {
    $project = workflowProject($this->lead);
    $this->actingAs($this->admin)->post('/admin/projects/'.$project->id.'/milestones', workflowMilestoneData())->assertSessionHasNoErrors();
    $milestone = $project->milestones()->sole();
    $this->patch('/admin/projects/'.$project->id, ['title' => $project->title, 'status' => 'completed'])->assertSessionHasErrors('status');
    $this->actingAs($this->customer)->post('/projects/'.$project->id.'/milestones/'.$milestone->id.'/approval', ['decision' => 'approve'])->assertSessionHasNoErrors();
    expect($milestone->refresh()->status)->toBe('completed');
    expect($milestone->approved_by)->toBe($this->customer->id);
    $this->get('/projects/'.$project->id)->assertViewHas('props', fn ($p) => $p['project']['progress'] === 100);
    $this->post('/projects/'.$project->id.'/milestones/'.$milestone->id.'/approval', ['decision' => 'request_changes', 'approval_note' => 'Stale browser request'])->assertSessionHasErrors('decision');
    $this->actingAs($this->admin)->patch('/admin/projects/'.$project->id, ['title' => $project->title, 'status' => 'completed'])->assertSessionHasNoErrors();
    expect($project->refresh()->completed_at)->not->toBeNull();
    expect($this->lead->refresh()->status)->toBe('completed');
});

test('change requests need feedback and milestone IDs are scoped to their project', function () {
    $project = workflowProject($this->lead);
    $milestone = $project->milestones()->create(workflowMilestoneData());
    $other = Project::create(['customer_id' => $this->customer->id, 'customer_name' => 'Other', 'customer_email' => $this->customer->email, 'title' => 'Other']);
    $url = '/projects/'.$project->id.'/milestones/'.$milestone->id.'/approval';
    $this->actingAs($this->customer)->post($url, ['decision' => 'request_changes'])->assertSessionHasErrors('approval_note');
    $this->post('/projects/'.$other->id.'/milestones/'.$milestone->id.'/approval', ['decision' => 'approve'])->assertNotFound();
    $this->post($url, ['decision' => 'request_changes', 'approval_note' => 'Please revise the layout'])->assertSessionHasNoErrors();
    expect($milestone->refresh()->status)->toBe('changes_requested');
    $this->actingAs($this->admin)->patch('/admin/projects/'.$other->id.'/milestones/'.$milestone->id, workflowMilestoneData())->assertNotFound();
});

test('approved work cannot be edited without a new review and closed projects freeze milestones', function () {
    $project = workflowProject($this->lead);
    $milestone = $project->milestones()->create(workflowMilestoneData(['status' => 'completed', 'approved_at' => now(), 'approved_by' => $this->customer->id]));
    $this->actingAs($this->admin)->patch('/admin/projects/'.$project->id.'/milestones/'.$milestone->id, workflowMilestoneData(['title' => 'Changed deliverable', 'status' => 'completed']))->assertSessionHasErrors('status');
    $this->patch('/admin/projects/'.$project->id.'/milestones/'.$milestone->id, workflowMilestoneData(['title' => 'Changed deliverable']))->assertSessionHasNoErrors();
    expect($milestone->refresh()->approved_at)->toBeNull();
    expect($milestone->status)->toBe('awaiting_approval');
    $project->update(['status' => 'cancelled']);
    $this->post('/admin/projects/'.$project->id.'/milestones', workflowMilestoneData())->assertSessionHasErrors('project');
});

test('project files are private scoped and removable only by their uploader or admin', function () {
    Storage::fake('local');
    $project = workflowProject($this->lead);
    $this->actingAs($this->admin)->post('/projects/'.$project->id.'/files', ['file' => UploadedFile::fake()->create('brief.pdf', 20, 'application/pdf')])->assertSessionHasNoErrors();
    $file = $project->files()->sole();
    Storage::disk('local')->assertExists($file->path);
    $this->actingAs($this->customer)->get('/projects/'.$project->id)->assertDontSee($file->path);
    $this->get('/projects/'.$project->id.'/files/'.$file->id)->assertOk()->assertDownload('brief.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->delete('/projects/'.$project->id.'/files/'.$file->id)->assertForbidden();
    $this->actingAs(User::factory()->create())->get('/projects/'.$project->id.'/files/'.$file->id)->assertNotFound();
    $other = Project::create(['customer_id' => $this->customer->id, 'customer_name' => 'Other', 'customer_email' => $this->customer->email, 'title' => 'Other']);
    $this->actingAs($this->customer)->get('/projects/'.$other->id.'/files/'.$file->id)->assertNotFound();
    $this->actingAs($this->admin)->delete('/projects/'.$project->id.'/files/'.$file->id)->assertSessionHasNoErrors();
    Storage::disk('local')->assertMissing($file->path);
    $this->assertDatabaseCount('project_files', 0);
});

test('project uploads reject executable content and files over the size limit', function () {
    Storage::fake('local');
    $project = workflowProject($this->lead);
    $this->actingAs($this->customer)->post('/projects/'.$project->id.'/files', ['file' => UploadedFile::fake()->create('page.html', 5, 'text/html')])->assertSessionHasErrors('file');
    $this->post('/projects/'.$project->id.'/files', ['file' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('file');
    $this->assertDatabaseCount('project_files', 0);
});

test('lead and project screens render with server side filters', function () {
    $project = workflowProject($this->lead, ['assigned_to' => $this->admin->id, 'due_on' => today()->subDay()]);
    $this->actingAs($this->admin);
    foreach (['/admin/requests', '/admin/requests/'.$this->lead->id, '/admin/projects', '/admin/projects/'.$project->id] as $url) {
        $this->get($url)->assertOk()->assertViewIs('react');
    }
    $this->get('/admin/projects?overdue=1')->assertViewHas('props', fn ($p) => $p['projects']['total'] === 1);
    $this->get('/admin/projects?status=completed')->assertViewHas('props', fn ($p) => $p['projects']['total'] === 0);
    $this->get('/admin/requests?search=does-not-exist')->assertViewHas('props', fn ($p) => $p['requests']['total'] === 0);
});
