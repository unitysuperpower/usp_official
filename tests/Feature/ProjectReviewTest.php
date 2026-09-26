<?php

use App\Mail\ContactReplyMail;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ContactMessage;
use App\Models\PageView;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\ImageOptimizationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    config(['webpush.key_file' => '/tmp/usp-review-no-push-keys', 'webpush.public_key' => null, 'webpush.private_key' => null]);
});

test('chat responses only disclose sender and assignee display identities', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $conversation = ChatConversation::create(['user_id' => $user->id, 'assigned_to' => $admin->id, 'status' => 'active']);
    ChatMessage::create(['conversation_id' => $conversation->id, 'user_id' => $admin->id, 'is_admin' => true, 'message' => 'Hello']);
    $this->actingAs($user)->getJson("/chat/{$conversation->id}/messages")->assertOk()
        ->assertJsonPath('messages.0.user', ['id' => $admin->id, 'name' => $admin->name])
        ->assertJsonPath('conversation.assigned_to', ['id' => $admin->id, 'name' => $admin->name])
        ->assertDontSee($admin->email);
    $this->postJson('/chat/send', ['conversation_id' => $conversation->id, 'message' => 'Thank you'])
        ->assertOk()->assertJsonPath('message.user', ['id' => $user->id, 'name' => $user->name]);
    $this->actingAs(User::factory()->create())->getJson("/chat/{$conversation->id}/messages")->assertForbidden();
    $this->postJson('/chat/send', ['conversation_id' => $conversation->id, 'message' => 'Not mine'])->assertForbidden();
    $this->actingAs($admin)->getJson("/admin/chat/{$conversation->id}/messages")->assertOk()->assertDontSee($user->email);
    $this->postJson('/admin/chat/send', ['conversation_id' => $conversation->id, 'message' => 'Welcome'])
        ->assertOk()->assertJsonPath('message.user', ['id' => $admin->id, 'name' => $admin->name]);
});

test('draft articles reject engagement and replies must belong to the same published article', function () {
    $user = User::factory()->create();
    $category = BlogCategory::create(['name' => 'News', 'slug' => 'news']);
    $draft = Blog::create(['category_id' => $category->id, 'user_id' => $user->id, 'title' => 'Draft', 'slug' => 'draft', 'content' => 'Draft', 'is_published' => false]);
    $article = Blog::create(['category_id' => $category->id, 'user_id' => $user->id, 'title' => 'Public', 'slug' => 'public', 'content' => 'Public', 'is_published' => true]);
    $parent = BlogComment::create(['blog_id' => $draft->id, 'user_id' => $user->id, 'comment' => 'Different article', 'is_approved' => true]);
    $this->actingAs($user)->postJson("/blogs/{$draft->id}/like")->assertNotFound();
    $this->post("/blogs/{$draft->id}/comment", ['comment' => 'Draft comment'])->assertNotFound();
    $this->post("/blogs/{$article->id}/comment", ['comment' => 'Wrong parent', 'parent_id' => $parent->id])->assertSessionHasErrors('parent_id');
    $parent->update(['blog_id' => $article->id, 'is_approved' => false]);
    $this->post("/blogs/{$article->id}/comment", ['comment' => 'Hidden parent', 'parent_id' => $parent->id])->assertSessionHasErrors('parent_id');
    $parent->update(['is_approved' => true]);
    $this->post("/blogs/{$article->id}/comment", ['comment' => 'Valid reply', 'parent_id' => $parent->id])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('blog_comments', ['blog_id' => $article->id, 'parent_id' => $parent->id, 'comment' => 'Valid reply']);
});

test('explicit false checkbox values keep resources unpublished and inactive', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = ServiceCategory::create(['name' => 'Web', 'slug' => 'web']);
    $blogCategory = BlogCategory::create(['name' => 'News', 'slug' => 'news']);
    $this->actingAs($admin)->post('/admin/services', ['title' => 'Web build', 'category_id' => $category->id, 'description' => 'Build', 'price' => 100, 'price_unit' => 'project', 'is_active' => '0', 'is_featured' => '0'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('services', ['slug' => 'web-build', 'is_active' => false, 'is_featured' => false]);
    $this->post('/admin/blogs', ['title' => 'Private draft', 'category_id' => $blogCategory->id, 'content' => 'Draft', 'is_published' => '0', 'is_featured' => '0'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('blogs', ['slug' => 'private-draft', 'is_published' => false, 'is_featured' => false]);
    $this->put("/admin/blog-categories/{$blogCategory->id}", ['name' => 'News', 'is_active' => '0'])->assertSessionHasNoErrors();
    expect($blogCategory->refresh()->is_active)->toBeFalse();
    $this->get('/blog-category/news')->assertNotFound();
});

test('category slug collisions produce validation errors instead of database exceptions', function (string $path, string $model) {
    $category = $model::create(['name' => 'Web design', 'slug' => 'web-design']);
    $other = $model::create(['name' => 'Other', 'slug' => 'other']);
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->post($path, ['name' => 'Web-design'])->assertSessionHasErrors('slug');
    $this->put("$path/{$other->id}", ['name' => 'Web design'])->assertSessionHasErrors('slug');
    $this->put("$path/{$category->id}", ['name' => 'Web design'])->assertSessionHasNoErrors();
})->with([
    ['/admin/categories', ServiceCategory::class],
    ['/admin/blog-categories', BlogCategory::class],
]);

test('chat polling and account pages do not inflate website page views', function () {
    $this->get('/')->assertOk();
    expect(PageView::count())->toBe(1);
    $this->actingAs(User::factory()->create())->getJson('/chat/widget')->assertOk();
    $this->get('/profile')->assertOk();
    $this->get('/robots.txt')->assertOk();
    expect(PageView::count())->toBe(1);
    $this->get('/services')->assertOk();
    expect(PageView::count())->toBe(2);
});

test('requests cannot target inactive services', function () {
    $category = ServiceCategory::create(['name' => 'Web', 'slug' => 'web']);
    $service = Service::create(['category_id' => $category->id, 'title' => 'Hidden', 'slug' => 'hidden', 'description' => 'Hidden service', 'price' => 100, 'price_unit' => 'project', 'is_active' => false]);
    $this->post('/contact', ['service_id' => $service->id, 'name' => 'Client', 'email' => 'client@example.com', 'message' => 'Please build this.'])->assertSessionHasErrors('service_id');
    $this->assertDatabaseCount('service_requests', 0);
});

test('unchecking mark as replied preserves inbox status', function () {
    Mail::fake();
    $message = ContactMessage::create(['name' => 'Client', 'email' => 'client@example.com', 'subject' => 'Help', 'message' => 'Need some help.', 'status' => 'pending']);
    $this->actingAs(User::factory()->create(['is_admin' => true]))->post("/admin/contact-messages/{$message->id}/reply", ['subject' => 'Re: Help', 'reply_message' => 'Here is your answer.', 'mark_as_replied' => '0'])->assertSessionHasNoErrors();
    expect($message->refresh()->status)->toBe('pending');
    Mail::assertSent(ContactReplyMail::class);
});

test('failed email delivery does not mark a contact message as replied', function () {
    Mail::shouldReceive('to')->once()->andReturnSelf();
    Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('Delivery failed'));
    $message = ContactMessage::create(['name' => 'Client', 'email' => 'client@example.com', 'subject' => 'Help', 'message' => 'Need some help.', 'status' => 'pending']);
    $this->withoutExceptionHandling()->actingAs(User::factory()->create(['is_admin' => true]));
    expect(fn () => $this->post("/admin/contact-messages/{$message->id}/reply", ['subject' => 'Re: Help', 'reply_message' => 'Here is your answer.', 'mark_as_replied' => '1']))->toThrow(RuntimeException::class, 'Delivery failed');
    expect($message->refresh()->status)->toBe('pending');
});

test('same-named images uploaded at the same time have separate files', function () {
    Storage::fake('public');
    $this->freezeTime();
    $optimizer = new ImageOptimizationService;
    $first = $optimizer->processImage(UploadedFile::fake()->image('cover.png'), 'blogs');
    $second = $optimizer->processImage(UploadedFile::fake()->image('cover.png'), 'blogs');
    expect($first)->not->toBe($second);
    Storage::disk('public')->assertExists([$first, $second]);
});

test('contact reply email renders its subject and escaped message content', function () {
    $message = ContactMessage::create(['name' => 'Client', 'email' => 'client@example.com', 'subject' => 'Help', 'message' => 'Original request']);
    $mail = new ContactReplyMail($message, 'Your answer', '<script>alert(1)</script>');
    expect($mail->envelope()->subject)->toBe('Your answer');
    expect($mail->render())->toContain('<title>Your answer</title>')->toContain('&lt;script&gt;')->not->toContain('<script>');
});
