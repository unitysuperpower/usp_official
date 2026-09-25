<?php

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->withoutVite();
});

test('public pages use React and preserve their page data', function (string $url, string $page) {
    $this->get($url)->assertOk()->assertViewIs('react')->assertViewHas('page', $page);
})->with([
    ['/', 'home'], ['/services', 'services.index'], ['/blogs', 'blogs.index'],
    ['/contact', 'contact'], ['/login', 'auth.login'], ['/register', 'auth.register'],
]);

test('all admin pages render and ordinary users cannot access them', function () {
    $urls = ['/admin/dashboard', '/admin/services', '/admin/services/create', '/admin/categories',
        '/admin/categories/create', '/admin/blogs', '/admin/blogs/create', '/admin/blog-categories',
        '/admin/blog-categories/create', '/admin/requests', '/admin/contact-messages',
        '/admin/chat', '/admin/analytics', '/admin/analytics/seo', '/admin/profile'];
    $this->actingAs(User::factory()->create());
    foreach ($urls as $url) {
        $this->get($url)->assertForbidden();
    }
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    foreach ($urls as $url) {
        $this->get($url)->assertOk()->assertViewIs('react');
    }
});

test('service status update binds requested record and validates status', function () {
    $category = ServiceCategory::create(['name' => 'Web', 'slug' => 'web', 'is_active' => true]);
    $service = Service::create(['title' => 'Website', 'slug' => 'website', 'category_id' => $category->id, 'description' => 'Website build', 'price' => 100, 'price_unit' => 'project', 'is_active' => true]);
    $request = ServiceRequest::create(['service_id' => $service->id, 'name' => 'Client', 'email' => 'client@example.com', 'message' => 'Build a website', 'status' => 'pending']);
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->patch('/admin/requests/'.$request->id.'/status', ['status' => 'completed'])->assertSessionHasNoErrors();
    expect($request->refresh()->status)->toBe('completed');
    $this->patch('/admin/requests/'.$request->id.'/status', ['status' => 'invalid'])->assertSessionHasErrors('status');
    $this->get('/admin/requests/'.$request->id)->assertOk()->assertViewHas('page', 'admin.requests.show');
    $this->get('/services/website')->assertOk()->assertViewHas('page', 'services.show');
});

test('public blog data does not expose author email or credentials', function () {
    $author = User::factory()->create(['email' => 'private-author@example.com']);
    $category = BlogCategory::create(['name' => 'News', 'slug' => 'news', 'is_active' => true]);
    Blog::create(['title' => 'An article', 'slug' => 'an-article', 'category_id' => $category->id, 'user_id' => $author->id, 'content' => '<p>Article content</p>', 'is_published' => true, 'published_at' => now()]);
    $this->get('/blogs/an-article')->assertOk()->assertViewHas('props', fn ($props) => $props['blog']['user'] === ['id' => $author->id, 'name' => $author->name])->assertDontSee('private-author@example.com');
    $this->get('/blogs')->assertOk()->assertDontSee('private-author@example.com');
});

test('customer dashboard only contains their requests', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $category = ServiceCategory::create(['name' => 'Web', 'slug' => 'web']);
    $service = Service::create(['title' => 'Website', 'slug' => 'website', 'category_id' => $category->id, 'description' => 'Website build', 'price' => 100, 'price_unit' => 'project']);
    foreach ([$user, $other] as $owner) {
        ServiceRequest::create(['service_id' => $service->id, 'user_id' => $owner->id, 'name' => $owner->name, 'email' => $owner->email, 'message' => 'Request for '.$owner->id]);
    }
    $this->actingAs($user)->get('/dashboard')->assertOk()->assertViewHas('props', fn ($props) => count($props['requests']['data']) === 1 && $props['requests']['data'][0]['user_id'] === $user->id);
});

test('contact form validates and stores valid messages', function () {
    $this->post('/contact', [])->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    $this->post('/contact', ['name' => 'Client', 'email' => 'client@example.com', 'subject' => 'New project', 'message' => 'Please help us build a new website.'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('contact_messages', ['subject' => 'New project']);
});

test('React profile endpoints update details and require current password', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user)->patch('/profile', ['name' => 'Updated name', 'email' => 'updated@example.com'])->assertSessionHasNoErrors();
    expect($user->refresh()->name)->toBe('Updated name');
    expect($user->email_verified_at)->toBeNull();
    $this->patch('/profile/password', ['current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasErrors('current_password');
    $this->patch('/profile/password', ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasNoErrors();
    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('opening React security settings never mutates two factor credentials', function () {
    $user = User::factory()->create();
    $secret = $user->two_factor_secret;
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->get('/settings/two-factor')->assertOk();
    expect($user->refresh()->two_factor_secret)->toBe($secret);
});

test('React chat summaries use snake case relation names', function () {
    $user = User::factory()->create();
    $conversation = \App\Models\ChatConversation::create(['user_id' => $user->id, 'subject' => 'Support', 'status' => 'active']);
    \App\Models\ChatMessage::create(['conversation_id' => $conversation->id, 'user_id' => $user->id, 'message' => 'A customer question', 'is_admin' => false]);
    $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin/chat')
        ->assertOk()
        ->assertViewHas('props', fn ($props) => $props['conversations']['data'][0]['latest_message']['message'] === 'A customer question');
});

test('editing services can clear the feature list', function () {
    $category = ServiceCategory::create(['name' => 'Web', 'slug' => 'web']);
    $service = Service::create(['title' => 'Website', 'slug' => 'website', 'category_id' => $category->id, 'description' => 'Website build', 'price' => 100, 'price_unit' => 'project', 'features' => ['Original feature']]);
    $this->actingAs(User::factory()->create(['is_admin' => true]))->put('/admin/services/'.$service->id, [
        'title' => 'Website', 'category_id' => $category->id, 'description' => 'Website build', 'price' => 100, 'price_unit' => 'project', 'features_text' => '',
    ])->assertSessionHasNoErrors();
    expect($service->refresh()->features)->toBe([]);
});

test('editing articles can clear their tags', function () {
    $author = User::factory()->create(['is_admin' => true]);
    $category = BlogCategory::create(['name' => 'News', 'slug' => 'news']);
    $blog = Blog::create(['title' => 'Article', 'slug' => 'article', 'category_id' => $category->id, 'user_id' => $author->id, 'content' => 'Article content', 'tags' => ['Original tag']]);
    $this->actingAs($author)->put('/admin/blogs/'.$blog->id, [
        'title' => 'Article', 'category_id' => $category->id, 'content' => 'Article content', 'tags' => '',
    ])->assertSessionHasNoErrors();
    expect($blog->refresh()->tags)->toBe([]);
});
