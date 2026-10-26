<?php

use App\Mail\NotificationMail;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\EducationAssignment;
use App\Models\EducationSession;
use App\Models\Role;
use App\Models\User;
use App\Support\EducationInsights;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    Mail::fake();
    $this->admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true]);
    $this->teacher = User::factory()->withoutTwoFactor()->create(['name' => 'Tara Teacher']);
    $this->teacher->syncRoles([Role::where('slug', 'instructor')->value('id')]);
    $this->course = Course::create(['title' => 'Smart course', 'slug' => 'smart-course', 'category' => 'Marketing', 'summary' => 'S', 'description' => 'D', 'instructor' => 'Tara', 'duration_hours' => 5, 'fee_minor' => 0, 'currency' => 'PKR', 'mode' => 'online', 'is_published' => true]);
    $this->course->instructors()->sync([$this->teacher->id]);
    $this->lessons = collect(range(1, 4))->map(fn ($i) => $this->course->lessons()->create(['title' => "Lesson {$i}", 'position' => $i, 'duration_minutes' => 30, 'content' => 'Text', 'is_published' => true]));
    // Halfway through the batch: students should have finished 2 of 4 lessons by now.
    $this->batch = $this->course->batches()->create(['name' => 'Cohort', 'starts_on' => today()->subDays(10), 'ends_on' => today()->addDays(10), 'applications_close_on' => today()->subDays(11), 'capacity' => 10, 'schedule' => 'Tue', 'is_open' => false]);
    $make = function (string $name, int $idleDays) {
        $user = User::factory()->withoutTwoFactor()->create(['name' => $name]);

        return CourseEnrollment::create(['user_id' => $user->id, 'course_batch_id' => $this->batch->id, 'phone' => '0300', 'motivation' => 'x', 'fee_minor' => 0, 'currency' => 'PKR', 'status' => 'active', 'enrolled_at' => now()->subDays(10), 'last_active_at' => now()->subDays($idleDays)]);
    };
    $this->good = $make('Good Student', 0);
    $this->late = $make('Late Student', 12);
    foreach ($this->lessons->take(2) as $lesson) {
        DB::table('course_lesson_progress')->insert(['course_enrollment_id' => $this->good->id, 'course_lesson_id' => $lesson->id, 'completed_at' => now()]);
    }
    $this->overdue = EducationAssignment::create(['course_batch_id' => $this->batch->id, 'title' => 'Persona', 'instructions' => 'Write', 'due_at' => now()->subDay(), 'allow_late' => true, 'is_published' => true, 'is_required' => true, 'pass_percent' => 60]);
    $this->base = '/admin/education/batches/'.$this->batch->id;
});

it('lets instructors run only the courses they are assigned to', function () {
    $other = Course::create(['title' => 'Other', 'slug' => 'other', 'category' => 'X', 'summary' => 'S', 'description' => 'D', 'instructor' => 'Y', 'duration_hours' => 1, 'fee_minor' => 0, 'currency' => 'PKR', 'mode' => 'online', 'is_published' => true]);
    $otherBatch = $other->batches()->create(['name' => 'B', 'starts_on' => today(), 'ends_on' => today()->addDay(), 'applications_close_on' => today(), 'capacity' => 1, 'schedule' => 'x', 'is_open' => true]);

    $this->actingAs($this->teacher)->get($this->base.'/classroom')->assertOk();
    $this->actingAs($this->teacher)->get("/admin/education/batches/{$otherBatch->id}/classroom")->assertForbidden();
    $this->actingAs($this->teacher)->post("/admin/education/batches/{$otherBatch->id}/sessions", [])->assertForbidden();
    $this->actingAs($this->teacher)->get('/admin/education/courses')->assertForbidden();
    $this->actingAs($this->teacher)->get('/dashboard')->assertRedirect('/admin/education/teaching');

    $batches = $this->actingAs($this->teacher)->get('/admin/education/teaching')->assertOk()->viewData('props')['batches'];
    expect(collect($batches)->pluck('id')->all())->toBe([$this->batch->id]);
    $this->actingAs($this->admin)->get("/admin/education/batches/{$otherBatch->id}/classroom")->assertOk();
});

it('flags students who fall behind and explains why', function () {
    $insights = EducationInsights::forBatch($this->batch);
    $good = $insights[$this->good->id];
    $late = $insights[$this->late->id];

    expect($good['level'])->toBe('on_track')->and($good['lessons_behind'])->toBe(0)->and($good['progress'])->toBe(50)
        ->and($late['level'])->toBe('at_risk')->and($late['lessons_behind'])->toBe(2)->and($late['missing'])->toBe(1)
        ->and(implode(' ', $late['reasons']))->toContain('2 lessons behind')->toContain('Persona')->toContain('12 days');

    $classroom = $this->actingAs($this->teacher)->get($this->base.'/classroom')->assertOk()->viewData('props');
    expect($classroom['insights'][0]['enrollment_id'])->toBe($this->late->id);
});

it('gives students a prioritised plan and tracks their streak', function () {
    $student = $this->good->student;
    EducationSession::create(['course_batch_id' => $this->batch->id, 'title' => 'Live Q&A', 'starts_at' => now()->addMinutes(5), 'status' => 'scheduled']);
    $props = $this->actingAs($student)->get('/learn/'.$this->good->id)->assertOk()->viewData('props');
    $types = collect($props['plan']['todo'])->pluck('type')->all();
    expect($types[0])->toBe('class')->and($props['plan']['todo'][0]['live'])->toBeTrue()
        ->and($types)->toContain('assignment', 'lesson')
        ->and($props['plan']['insight']['next_lesson']['id'])->toBe($this->lessons[2]->id)
        ->and($props['plan']['insight'])->not->toHaveKey('_quizzes');

    DB::table('education_activity_days')->insert([['course_enrollment_id' => $this->good->id, 'day' => now('Asia/Karachi')->subDay()->toDateString()], ['course_enrollment_id' => $this->good->id, 'day' => now('Asia/Karachi')->subDays(2)->toDateString()]]);
    $this->postJson('/learn/'.$this->good->id.'/lessons/'.$this->lessons[3]->id.'/open')->assertOk();
    expect($this->good->fresh()->last_lesson_id)->toBe($this->lessons[3]->id)
        ->and(EducationInsights::forEnrollment($this->good->fresh())['streak'])->toBe(3);

    $this->actingAs($this->late->student)->postJson('/learn/'.$this->good->id.'/lessons/'.$this->lessons[3]->id.'/open')->assertNotFound();
});

it('completes a lesson and continues to the next unfinished one', function () {
    $this->actingAs($this->good->student)->post('/learn/'.$this->good->id.'/lessons/'.$this->lessons[2]->id.'/complete', ['continue' => 1])
        ->assertRedirect('/learn/'.$this->good->id.'?tab=lessons&lesson='.$this->lessons[3]->id);
    $this->post('/learn/'.$this->good->id.'/lessons/'.$this->lessons[3]->id.'/complete', ['continue' => 1])
        ->assertRedirect('/learn/'.$this->good->id.'?tab=overview');
    $this->putJson('/learn/'.$this->good->id.'/lessons/'.$this->lessons[0]->id.'/note', ['body' => 'Autosaved'])->assertOk()->assertJsonStructure(['saved_at']);
    expect(DB::table('education_notes')->where('course_enrollment_id', $this->good->id)->value('body'))->toBe('Autosaved');
});

it('emails one reminder per student per day from the classroom', function () {
    $this->actingAs($this->teacher)->post("{$this->base}/students/{$this->late->id}/nudge", ['message' => 'Need help?'])->assertSessionHasNoErrors();
    Mail::assertSent(NotificationMail::class, fn ($mail) => $mail->hasTo($this->late->student->email) && in_array('Need help?', $mail->lines, true) && collect($mail->lines)->contains(fn ($l) => str_contains($l, 'Persona')));
    $this->actingAs($this->teacher)->post("{$this->base}/students/{$this->late->id}/nudge")->assertSessionHasErrors('message');
    Mail::assertSentCount(1);
});

it('exports a gradebook that spreadsheets cannot execute', function () {
    $this->late->student->forceFill(['name' => '=HYPERLINK("http://evil")'])->save();
    $csv = $this->actingAs($this->teacher)->get($this->base.'/gradebook.csv')->assertOk()->streamedContent();
    expect($csv)->toContain('Assignment: Persona')->toContain('Good Student')->toContain("'=HYPERLINK")->toContain('missing');
});

it('sends scheduled class, deadline and inactivity reminders only once', function () {
    EducationSession::create(['course_batch_id' => $this->batch->id, 'title' => 'Tomorrow class', 'starts_at' => now()->addHours(20), 'status' => 'scheduled']);
    EducationAssignment::create(['course_batch_id' => $this->batch->id, 'title' => 'Due soon', 'instructions' => 'x', 'due_at' => now()->addHours(6), 'allow_late' => true, 'is_published' => true, 'is_required' => false, 'pass_percent' => 60]);

    $this->artisan('education:reminders')->assertSuccessful();
    // 2 class + 2 due-soon + 1 inactivity (the late student).
    Mail::assertSentCount(5);
    $this->artisan('education:reminders')->assertSuccessful();
    Mail::assertSentCount(5);
});

it('reorders and deletes lessons and only deletes empty batches', function () {
    $edit = "/admin/education/courses/{$this->course->id}";
    $this->actingAs($this->admin)->patch("{$edit}/lessons/{$this->lessons[3]->id}/move", ['direction' => 'up'])->assertRedirect();
    expect($this->course->lessons()->pluck('title')->all())->toBe(['Lesson 1', 'Lesson 2', 'Lesson 4', 'Lesson 3']);
    $this->delete("{$edit}/lessons/{$this->lessons[0]->id}")->assertRedirect();
    expect($this->course->lessons()->count())->toBe(3);

    $this->delete("{$edit}/batches/{$this->batch->id}")->assertSessionHasErrors('batch');
    $empty = $this->course->batches()->create(['name' => 'Empty', 'starts_on' => today(), 'ends_on' => today(), 'applications_close_on' => today(), 'capacity' => 1, 'schedule' => 'x', 'is_open' => true]);
    $this->delete("{$edit}/batches/{$empty->id}")->assertSessionHasNoErrors();
    $this->assertModelMissing($empty);
});

it('only assigns staff who can teach as course instructors', function () {
    $student = User::factory()->withoutTwoFactor()->create();
    $payload = ['title' => 'Smart course', 'slug' => 'smart-course', 'category' => 'Marketing', 'level' => 'beginner', 'mode' => 'online', 'summary' => 'S', 'description' => 'D', 'instructor' => 'Tara', 'duration_hours' => 5, 'fee' => 0, 'is_published' => 1, 'instructors_submitted' => 1, 'instructor_ids' => [$this->teacher->id, $student->id]];
    $this->actingAs($this->admin)->put("/admin/education/courses/{$this->course->id}", $payload)->assertSessionHasNoErrors();
    expect($this->course->instructors()->pluck('users.id')->all())->toBe([$this->teacher->id]);
});
