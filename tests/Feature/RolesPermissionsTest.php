<?php

use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->super = User::factory()->withoutTwoFactor()->create(['is_admin' => true]);
});

function staff(string $slug): User
{
    $user = User::factory()->withoutTwoFactor()->create();
    $user->syncRoles([Role::where('slug', $slug)->value('id')]);

    return $user->fresh();
}

it('seeds the default roles and makes new registrations learners', function () {
    expect(Role::pluck('slug')->all())->toContain('super-admin', 'admin', 'instructor', 'editor', 'support', 'learner');
    expect(Role::where('slug', 'admin')->first()->permissions)->not->toContain('roles.manage')->toContain('users.manage');

    $this->post('/register', ['name' => 'New Student', 'email' => 'new@example.com', 'password' => 'Sturdy-pass-123!', 'password_confirmation' => 'Sturdy-pass-123!']);
    $user = User::where('email', 'new@example.com')->first();
    expect($user->roles->pluck('slug')->all())->toBe(['learner'])->and($user->is_admin)->toBeFalse();
});

it('limits each staff role to its own admin areas', function () {
    $editor = staff('editor');
    expect($editor->is_admin)->toBeTrue();
    $this->actingAs($editor)->get('/admin/blogs')->assertOk();
    $this->actingAs($editor)->get('/admin/store/orders')->assertForbidden();
    $this->actingAs($editor)->get('/admin/chat')->assertForbidden();
    $this->actingAs($editor)->get('/admin/users')->assertForbidden();

    $instructor = staff('instructor');
    $this->actingAs($instructor)->get('/admin/education/overview')->assertOk();
    $this->actingAs($instructor)->get('/admin/education/courses')->assertForbidden();
    $this->actingAs($instructor)->get('/admin/education/payments')->assertForbidden();

    $support = staff('support');
    $this->actingAs($support)->get('/admin/chat')->assertOk();
    $this->actingAs($support)->get('/admin/store/products')->assertForbidden();

    $this->actingAs(staff('learner'))->get('/admin/dashboard')->assertForbidden();
});

it('sends staff without the overview to their first permitted page', function () {
    $role = Role::create(['slug' => 'writer', 'name' => 'Writer', 'permissions' => ['blogs.manage']]);
    $user = User::factory()->withoutTwoFactor()->create();
    $user->syncRoles([$role->id]);
    $this->actingAs($user)->get('/dashboard')->assertRedirect('/admin/blogs');
});

it('lets a super admin create roles and recalculates admin access', function () {
    $learner = staff('learner');
    $role = Role::where('slug', 'learner')->first();
    $this->actingAs($this->super)->put("/admin/roles/{$role->id}", ['name' => $role->name, 'permissions' => ['chat.manage']])->assertRedirect();
    expect($learner->fresh()->is_admin)->toBeTrue();

    $this->actingAs($this->super)->post('/admin/roles', ['name' => 'Course Manager', 'permissions' => ['education.courses', 'bogus.key']])->assertSessionHasErrors('permissions.1');
    $this->actingAs($this->super)->post('/admin/roles', ['name' => 'Course Manager', 'permissions' => ['education.courses']])->assertSessionHasNoErrors();
    expect(Role::where('slug', 'course-manager')->value('permissions'))->toBe(['education.courses']);

    $super = Role::where('is_system', true)->first();
    $this->actingAs($this->super)->put("/admin/roles/{$super->id}", ['name' => 'Hacked'])->assertForbidden();
    $this->actingAs($this->super)->delete("/admin/roles/{$super->id}")->assertForbidden();
});

it('blocks role escalation by admins who are not super admins', function () {
    $admin = staff('admin');
    $target = staff('learner');
    $super = Role::where('is_system', true)->value('id');

    $this->actingAs($admin)->get('/admin/roles')->assertForbidden();
    $this->actingAs($admin)->put("/admin/users/{$target->id}/roles", ['roles' => [$super]])->assertSessionHasErrors('roles');
    expect($target->fresh()->isSuperAdmin())->toBeFalse();

    $this->actingAs($admin)->put("/admin/users/{$target->id}/roles", ['roles' => [Role::where('slug', 'editor')->value('id')]])->assertSessionHasNoErrors();
    expect($target->fresh()->roles->pluck('slug')->all())->toBe(['editor']);

    $custom = Role::create(['slug' => 'roles-only', 'name' => 'Roles only', 'permissions' => ['roles.manage']]);
    $this->actingAs($admin)->put("/admin/users/{$target->id}/roles", ['roles' => [$custom->id]])->assertSessionHasErrors('roles');
});

it('keeps at least one super admin', function () {
    $learner = Role::where('slug', 'learner')->value('id');
    $this->actingAs($this->super)->put("/admin/users/{$this->super->id}/roles", ['roles' => [$learner]])->assertSessionHasErrors('roles');
    expect($this->super->fresh()->isSuperAdmin())->toBeTrue();
});

it('only shows support conversations to chat agents', function () {
    $this->actingAs(staff('editor'))->getJson('/chat/widget')->assertOk()->assertJsonPath('conversations', []);
    $this->actingAs(staff('support'))->getJson('/chat/widget')->assertOk();
});
