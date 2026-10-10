<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    $this->admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true]);
    $this->data = ['title' => 'Design systems', 'slug' => 'design-systems', 'category' => 'Design', 'level' => 'beginner', 'mode' => 'online', 'summary' => 'Build better interfaces', 'description' => 'Practical design', 'instructor' => 'Instructor', 'duration_hours' => 20, 'fee' => 100, 'is_published' => 1, 'is_featured' => 1, 'visual_theme' => 'violet', 'carousel_position' => 2];
});

test('admins upload replace retain and remove course covers', function () {
    $this->actingAs($this->admin)->post('/admin/education/courses', $this->data + ['cover' => UploadedFile::fake()->image('cover.png', 800, 500)])->assertSessionHasNoErrors();
    $course = Course::sole();
    $first = $course->cover_image;
    Storage::disk('public')->assertExists($first);
    expect($course->is_featured)->toBeTrue()->and($course->visual_theme)->toBe('violet');
    $url = '/admin/education/courses/'.$course->id;
    $this->put($url, $this->data)->assertSessionHasNoErrors();
    expect($course->refresh()->cover_image)->toBe($first);
    $this->put($url, $this->data + ['cover' => UploadedFile::fake()->image('new.jpg')])->assertSessionHasNoErrors();
    $second = $course->refresh()->cover_image;
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
    $this->put($url, $this->data + ['remove_cover' => 1])->assertSessionHasNoErrors();
    expect($course->refresh()->cover_image)->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

test('carousel only exposes published featured courses in order with a limit', function () {
    for ($i = 1; $i <= 15; $i++) {
        Course::create(array_merge($this->data, ['slug' => 'course-'.$i, 'fee_minor' => 10000, 'carousel_position' => 16 - $i]));
    }
    Course::first()->update(['is_published' => false]);
    Course::where('slug', 'course-15')->update(['is_featured' => false]);
    $this->get('/courses')->assertOk()->assertViewHas('props', function ($props) {
        $featured = $props['featured'];

        return count($featured) === 12 && $featured[0]['slug'] === 'course-14'
            && collect($featured)->every(fn ($c) => $c['is_published'] && $c['is_featured']);
    });
});

test('invalid graphics settings and unauthorized writes are rejected', function () {
    $this->actingAs(User::factory()->withoutTwoFactor()->create())->post('/admin/education/courses', $this->data)->assertForbidden();
    $this->actingAs($this->admin)->post('/admin/education/courses', array_merge($this->data, ['visual_theme' => 'unknown', 'carousel_position' => 0, 'cover' => UploadedFile::fake()->create('cover.svg', 10, 'image/svg+xml')]))->assertSessionHasErrors(['visual_theme', 'carousel_position', 'cover']);
    expect(Course::count())->toBe(0);
});
