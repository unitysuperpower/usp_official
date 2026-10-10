<?php

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\ContactMessage;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

test('administrative lists validate page sizes', function (string $path) {
    $this->get($path.'?per_page=10')->assertOk();
    $this->getJson($path.'?per_page=999')->assertUnprocessable();
    $this->getJson($path.'?per_page[]=20')->assertUnprocessable();
})->with(['/admin/dashboard', '/admin/categories', '/admin/blog-categories', '/admin/services', '/admin/blogs', '/admin/projects', '/admin/requests', '/admin/contact-messages', '/admin/chat', '/admin/education/courses', '/admin/education/enrollments', '/admin/education/payments']);

test('pagination retains filters and reports accurate page totals', function () {
    for ($i = 0; $i < 25; $i++) {
        ServiceCategory::create(['name' => 'Design '.$i, 'slug' => 'design-'.$i]);
    }
    $this->get('/admin/categories?search=Design&per_page=10&page=2')->assertOk()
        ->assertViewHas('props', fn ($p) => $p['categories']['per_page'] === 10 && $p['categories']['from'] === 11 && count($p['categories']['data']) === 10 && str_contains($p['categories']['next_page_url'], 'search=Design') && str_contains($p['categories']['next_page_url'], 'per_page=10'));
});

test('dashboard work queues use live records and recent requests are paginated', function () {
    $category = ServiceCategory::create(['name' => 'Development', 'slug' => 'development']);
    $service = Service::create(['category_id' => $category->id, 'title' => 'Websites', 'slug' => 'websites', 'description' => 'Build websites', 'price' => 100, 'price_unit' => 'project', 'is_active' => true]);
    for ($i = 0; $i < 12; $i++) {
        ServiceRequest::create(['service_id' => $service->id, 'name' => 'Customer '.$i, 'email' => 'person'.$i.'@example.test', 'message' => 'Please contact me', 'lead_stage' => $i === 0 ? 'won' : 'new', 'follow_up_on' => today()->subDay()]);
    }
    ContactMessage::create(['name' => 'A customer', 'email' => 'contact@example.test', 'subject' => 'A question', 'message' => 'Please get in touch', 'status' => 'pending']);
    $this->get('/admin/dashboard')->assertOk()->assertViewHas('props', fn ($p) => $p['stats']['open_leads'] === 11 && $p['attention']['overdue_follow_ups'] === 11 && $p['attention']['pending_messages'] === 1 && $p['recentRequests']['total'] === 12 && count($p['recentRequests']['data']) === 10 && $p['leadStages']['won'] === 1);
    $this->actingAs(User::factory()->withoutTwoFactor()->create())->get('/admin/dashboard')->assertForbidden();
});

test('categories containing content cannot be deleted by a table action', function () {
    $category = BlogCategory::create(['name' => 'News', 'slug' => 'news']);
    $blog = Blog::create(['title' => 'Published work', 'slug' => 'published-work', 'category_id' => $category->id, 'user_id' => $this->admin->id, 'content' => 'Article body']);
    $this->delete('/admin/blog-categories/'.$category->id)->assertSessionHasErrors('category');
    expect($blog->fresh())->not->toBeNull();
    $blog->delete();
    $this->delete('/admin/blog-categories/'.$category->id)->assertRedirect('/admin/blog-categories');
    expect($category->fresh())->toBeNull();
});

test('contact table filters reject invalid input and modal updates persist', function () {
    $message = ContactMessage::create(['name' => 'Visitor', 'email' => 'visitor@example.test', 'subject' => 'Help', 'message' => 'Need help', 'status' => 'pending']);
    $this->getJson('/admin/contact-messages?status=invalid')->assertUnprocessable();
    $this->getJson('/admin/contact-messages?search[]=invalid')->assertUnprocessable();
    $this->from('/admin/contact-messages')->put('/admin/contact-messages/'.$message->id, ['status' => 'archived', 'admin_notes' => 'Resolved in call', '_modal' => 'contact'])->assertRedirect('/admin/contact-messages')->assertSessionHasNoErrors();
    expect($message->fresh()->status)->toBe('archived');
});
