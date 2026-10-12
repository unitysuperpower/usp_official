<?php

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\EducationAssignment;
use App\Models\EducationSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withoutVite();
    $this->student = User::factory()->withoutTwoFactor()->create();
    $this->admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true]);
    $this->course = Course::create(['title' => 'Learning design', 'slug' => 'learning-design', 'category' => 'Design', 'summary' => 'Practical design', 'description' => 'Course content', 'instructor' => 'Teacher', 'duration_hours' => 20, 'fee_minor' => 10000, 'currency' => 'PKR', 'is_published' => true]);
    $this->batch = $this->course->batches()->create(['name' => 'Autumn', 'starts_on' => today()->addDays(10), 'ends_on' => today()->addDays(40), 'applications_close_on' => today()->addDays(5), 'capacity' => 20, 'schedule' => 'Monday', 'is_open' => true]);
    $this->lesson = $this->course->lessons()->create(['title' => 'First lesson', 'content' => 'Private content', 'is_published' => true]);
    $this->enrollment = CourseEnrollment::create(['user_id' => $this->student->id, 'course_batch_id' => $this->batch->id, 'phone' => '03001234567', 'motivation' => 'Learn', 'fee_minor' => 10000, 'currency' => 'PKR', 'status' => 'active', 'enrolled_at' => now()->subDays(10)]);
});
test('notes are private scoped editable and removable', function () {
    $url = '/learn/'.$this->enrollment->id.'/lessons/'.$this->lesson->id.'/note';
    $this->actingAs($this->student)->put($url, ['body' => 'My private notes'])->assertSessionHasNoErrors();
    $this->get('/learn/'.$this->enrollment->id)->assertOk()->assertViewHas('props', fn ($p) => $p['notes'][$this->lesson->id] === 'My private notes');
    $this->actingAs($this->admin)->put($url, ['body' => 'Overwrite'])->assertNotFound();
    $this->actingAs($this->student)->put($url, ['body' => 'Updated'])->assertSessionHasNoErrors();
    expect(DB::table('education_notes')->count())->toBe(1);
    $this->put($url, ['body' => ''])->assertSessionHasNoErrors();
    expect(DB::table('education_notes')->count())->toBe(0);
    $this->lesson->update(['is_published' => false]);
    $this->put($url, ['body' => 'Hidden'])->assertNotFound();
    $this->lesson->update(['is_published' => true]);
    $this->enrollment->update(['status' => 'accepted']);
    $this->put($url, ['body' => 'Not enrolled'])->assertForbidden();
});
test('announcements require admin and only reach enrolled students', function () {
    $url = '/admin/education/batches/'.$this->batch->id.'/announcements';
    $this->actingAs($this->student)->post($url, ['title' => 'Notice', 'body' => 'Private class update'])->assertForbidden();
    $this->actingAs($this->admin)->post($url, ['title' => 'Notice', 'body' => 'Private class update'])->assertSessionHasNoErrors();
    $this->actingAs($this->student)->get('/learn/'.$this->enrollment->id)->assertSee('Private class update');
    $this->enrollment->update(['status' => 'accepted']);
    $this->get('/learn/'.$this->enrollment->id)->assertDontSee('Private class update');
    $this->get('/courses/learning-design')->assertDontSee('Private class update');
    $id = DB::table('education_announcements')->value('id');
    $this->actingAs($this->admin)->delete($url.'/'.$id)->assertRedirect();
    expect(DB::table('education_announcements')->count())->toBe(0);
});
test('receipt requires approved payment and ownership', function () {
    $payment = $this->enrollment->payments()->create(['method' => 'bank_transfer', 'reference' => 'RECEIPT-TEST', 'amount_minor' => 10000, 'currency' => 'PKR', 'status' => 'pending']);
    $url = '/education/payments/'.$payment->id.'/receipt';
    $this->actingAs($this->student)->get($url)->assertNotFound();
    $payment->update(['status' => 'approved']);
    $this->get($url)->assertOk()->assertSee('Payment received')->assertSee('100.00')->assertHeader('Cache-Control', 'no-store, private');
    $this->actingAs(User::factory()->withoutTwoFactor()->create())->get($url)->assertNotFound();
    $this->actingAs($this->admin)->get($url)->assertOk();
});
test('discovery filters by subject and price without exposing drafts', function () {
    Course::create(['title' => 'Advanced code', 'slug' => 'advanced-code', 'category' => 'Development', 'summary' => 'Code', 'description' => 'Code', 'instructor' => 'Teacher', 'duration_hours' => 10, 'fee_minor' => 5000, 'currency' => 'PKR', 'is_published' => true]);
    $this->get('/courses?category=Design')->assertOk()->assertViewHas('props', fn ($p) => count($p['courses']['data']) === 1 && $p['courses']['data'][0]['slug'] === 'learning-design');
    $this->get('/courses?sort=price_low')->assertViewHas('props', fn ($p) => $p['courses']['data'][0]['slug'] === 'advanced-code');
    $this->get('/courses')->assertViewHas('props', fn ($p) => $p['courses']['data'][0]['next_batch_on'] !== null);
});
test('education overview keeps currencies separate and excludes unapproved receipts', function () {
    $this->enrollment->payments()->create(['method' => 'bank_transfer', 'reference' => 'APPROVED', 'amount_minor' => 10000, 'currency' => 'PKR', 'status' => 'approved']);
    $this->enrollment->payments()->create(['method' => 'bank_transfer', 'reference' => 'PENDING', 'amount_minor' => 20000, 'currency' => 'PKR', 'status' => 'pending']);
    $this->actingAs($this->student)->get('/admin/education/overview')->assertForbidden();
    $this->actingAs($this->admin)->get('/admin/education/overview')->assertOk()->assertViewHas('props', fn ($p) => $p['payments'][0]['collected'] == 10000 && $p['pendingPayments'] === 1 && count($p['followups']) === 1);
});
test('student deadlines exclude other batches and drafts', function () {
    EducationAssignment::create(['course_batch_id' => $this->batch->id, 'title' => 'Design task', 'instructions' => 'Work', 'due_at' => now()->addDays(2), 'is_published' => true, 'pass_percent' => 50]);
    EducationAssignment::create(['course_batch_id' => $this->batch->id, 'title' => 'Draft task', 'instructions' => 'Work', 'due_at' => now()->addDays(1), 'is_published' => false, 'pass_percent' => 50]);
    $this->actingAs($this->student)->get('/learn')->assertOk()->assertViewHas('props', fn ($p) => count($p['deadlines']) === 1 && $p['deadlines'][0]['title'] === 'Design task');
});

test('student dashboard filters only owned courses and highlights unfinished published lessons', function () {
    $other = User::factory()->withoutTwoFactor()->create();
    CourseEnrollment::create(['user_id' => $other->id, 'motivation' => 'Learn', 'course_batch_id' => $this->batch->id, 'phone' => '03001234567', 'fee_minor' => 10000, 'currency' => 'PKR', 'status' => 'active']);
    $this->actingAs($this->student)->get('/learn?status=active&search=Learning')->assertOk()->assertViewHas('props', fn ($p) => $p['enrollments']['total'] === 1 && $p['continueLearning']['lesson_id'] === $this->lesson->id);
    $this->get('/learn?status=completed')->assertViewHas('props', fn ($p) => $p['enrollments']['total'] === 0 && $p['stats']['active'] === 1);
    $this->get('/learn?search=nomatch')->assertViewHas('props', fn ($p) => $p['enrollments']['total'] === 0);
    DB::table('course_lesson_progress')->insert(['course_enrollment_id' => $this->enrollment->id, 'course_lesson_id' => $this->lesson->id, 'completed_at' => now()]);
    $this->get('/learn')->assertViewHas('props', fn ($p) => $p['continueLearning'] === null);
});
test('application trend fills missing days and uses Pakistan calendar dates', function () {
    $this->travelTo(Carbon::parse('2026-10-09 01:00:00', 'Asia/Karachi'));
    $this->enrollment->forceFill(['created_at' => Carbon::parse('2026-10-08 20:30:00', 'UTC')])->save();
    $this->actingAs($this->admin)->get('/admin/education/overview')->assertOk()->assertViewHas('props', fn ($p) => count($p['trend']) === 30 && $p['trend']['2026-10-09'] === 1 && $p['trend']['2026-10-08'] === 0);
});

test('learning calendar only contains upcoming active course events', function () {
    EducationSession::create(['course_batch_id' => $this->batch->id, 'title' => 'Upcoming class', 'starts_at' => now()->addDay(), 'status' => 'scheduled', 'notes' => 'PRIVATE_NOTES']);
    EducationSession::create(['course_batch_id' => $this->batch->id, 'title' => 'Cancelled class', 'starts_at' => now()->addDay(), 'status' => 'cancelled']);
    EducationSession::create(['course_batch_id' => $this->batch->id, 'title' => 'Old class', 'starts_at' => now()->subDay(), 'status' => 'scheduled']);
    EducationAssignment::create(['course_batch_id' => $this->batch->id, 'title' => 'Upcoming assignment', 'instructions' => 'PRIVATE_ASSIGNMENT', 'due_at' => now()->addDays(2), 'is_published' => true, 'pass_percent' => 50]);
    EducationAssignment::create(['course_batch_id' => $this->batch->id, 'title' => 'Draft assignment', 'instructions' => 'Draft', 'due_at' => now()->addDays(2), 'is_published' => false, 'pass_percent' => 50]);
    $this->actingAs($this->student)->get('/learn/calendar')->assertOk()->assertViewHas('props', fn ($p) => count($p['events']) === 2)->assertDontSee('PRIVATE_NOTES')->assertDontSee('PRIVATE_ASSIGNMENT');
    $this->actingAs(User::factory()->withoutTwoFactor()->create())->get('/learn/calendar')->assertViewHas('props', fn ($p) => count($p['events']) === 0);
    $this->enrollment->update(['status' => 'accepted']);
    $this->actingAs($this->student)->get('/learn/calendar')->assertViewHas('props', fn ($p) => count($p['events']) === 0);
});
test('calendar download is private escaped UTF8 and contains no meeting links', function () {
    $this->batch->update(['meeting_url' => 'https://example.test/private-meeting']);
    EducationSession::create(['course_batch_id' => $this->batch->id, 'title' => str_repeat('م', 80)."\nBEGIN:VEVENT;test,", 'starts_at' => now()->addDay(), 'status' => 'scheduled']);
    $this->get('/learn/calendar/download')->assertRedirect('/login');
    $response = $this->actingAs($this->student)->get('/learn/calendar/download')->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->assertHeader('Cache-Control', 'no-store, private');
    $content = $response->getContent();
    expect($content)->toContain('BEGIN:VCALENDAR')->not->toContain('private-meeting');
    expect(substr_count($content, "\r\nBEGIN:VEVENT\r\n"))->toBe(1);
    foreach (explode("\r\n", $content) as $line) {
        expect(strlen($line))->toBeLessThanOrEqual(75);
        expect(mb_check_encoding($line, 'UTF-8'))->toBeTrue();
    }
    expect(str_replace("\r\n ", '', $content))->toContain('\nBEGIN:VEVENT\;test\,');
});
