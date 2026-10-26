<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
});

it('stores editor images for content staff and returns a site-relative URL', function () {
    $editor = User::factory()->withoutTwoFactor()->create();
    $editor->syncRoles([Role::where('slug', 'editor')->value('id')]);

    $url = $this->actingAs($editor)->postJson('/admin/editor/images', ['image' => UploadedFile::fake()->image('photo.jpg', 1200, 800)])
        ->assertOk()->json('url');
    expect($url)->toStartWith('/storage/editor/')->toEndWith('.webp');
    Storage::disk('public')->assertExists(substr($url, strlen('/storage/')));
});

it('refuses editor uploads from people who cannot edit content', function () {
    $support = User::factory()->withoutTwoFactor()->create();
    $support->syncRoles([Role::where('slug', 'support')->value('id')]);
    $this->actingAs($support)->postJson('/admin/editor/images', ['image' => UploadedFile::fake()->image('a.png')])->assertForbidden();

    $learner = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($learner)->postJson('/admin/editor/images', ['image' => UploadedFile::fake()->image('a.png')])->assertForbidden();
    $this->post('/logout');
    $this->postJson('/admin/editor/images', ['image' => UploadedFile::fake()->image('a.png')])->assertUnauthorized();
});

it('accepts only images', function () {
    $admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true]);
    $this->actingAs($admin)->postJson('/admin/editor/images', ['image' => UploadedFile::fake()->create('x.svg', 3, 'image/svg+xml')])->assertUnprocessable();
    $this->actingAs($admin)->postJson('/admin/editor/images', ['image' => UploadedFile::fake()->create('x.php', 3, 'text/plain')])->assertUnprocessable();
});
