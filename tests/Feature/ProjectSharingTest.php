<?php

use App\Mail\NotificationMail;
use App\Models\MailSetting;
use App\Models\Project;
use App\Models\User;
use App\Support\SiteMail;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    Mail::fake();
    SiteMail::forget();
    MailSetting::create(['id' => 1, 'admin_address' => 'owner@example.com']);
    $this->admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true]);
    $this->client = User::factory()->withoutTwoFactor()->create(['email' => 'client@example.com']);
    $this->project = Project::create(['title' => 'Company website', 'customer_name' => 'Client Co', 'customer_email' => 'client@example.com', 'status' => 'in_progress']);
});

it('warns the admin and suggests the matching account while the project is not shared', function () {
    $this->actingAs($this->client)->get('/projects/'.$this->project->id)->assertNotFound();

    $this->actingAs($this->admin)->get('/admin/projects/'.$this->project->id)->assertOk()
        ->assertSee('suggestedAccountEmail', false)->assertSee('client@example.com');
});

it('shares the project with the client account and emails them', function () {
    $this->actingAs($this->admin)->patch('/admin/projects/'.$this->project->id.'/customer', ['customer_account_email' => 'client@example.com'])->assertSessionHasNoErrors();

    expect($this->project->fresh()->customer_id)->toBe($this->client->id);
    $this->actingAs($this->client)->get('/projects/'.$this->project->id)->assertOk();
    Mail::assertSent(NotificationMail::class, fn ($m) => $m->hasTo('client@example.com') && str_contains($m->subjectLine, 'Company website'));
});

it('rejects linking an email without a customer account', function () {
    $this->actingAs($this->admin)->patch('/admin/projects/'.$this->project->id.'/customer', ['customer_account_email' => 'nobody@example.com'])
        ->assertSessionHasErrors('customer_account_email');
    expect($this->project->fresh()->customer_id)->toBeNull();
});

it('sends a milestone for approval, lets the client approve and alerts the team', function () {
    $this->project->update(['customer_id' => $this->client->id]);
    $milestone = $this->project->milestones()->create(['title' => 'Homepage design', 'status' => 'pending', 'requires_approval' => false]);

    $this->actingAs($this->admin)->post('/admin/projects/'.$this->project->id.'/milestones/'.$milestone->id.'/request-approval')->assertSessionHasNoErrors();
    expect($milestone->fresh())->status->toBe('awaiting_approval')->requires_approval->toBeTrue();
    Mail::assertSent(NotificationMail::class, fn ($m) => $m->hasTo('client@example.com') && $m->subjectLine === 'Please review: Homepage design');

    $this->actingAs($this->client)->post('/projects/'.$this->project->id.'/milestones/'.$milestone->id.'/approval', ['decision' => 'approve'])->assertSessionHasNoErrors();
    expect($milestone->fresh()->status)->toBe('completed');
    Mail::assertSent(NotificationMail::class, fn ($m) => $m->hasTo('owner@example.com') && str_starts_with($m->subjectLine, 'Approved: Homepage design'));
});

it('requires a shared project before asking for approval', function () {
    $milestone = $this->project->milestones()->create(['title' => 'Logo', 'status' => 'pending', 'requires_approval' => true]);

    $this->actingAs($this->admin)->post('/admin/projects/'.$this->project->id.'/milestones/'.$milestone->id.'/request-approval')->assertSessionHasErrors('project');
    expect($milestone->fresh()->status)->toBe('pending');
});
