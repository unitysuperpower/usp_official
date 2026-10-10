<?php

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\EducationAssignment;
use App\Models\EducationQuiz;
use App\Models\EducationQuizAttempt;
use App\Models\EducationSession;
use App\Models\EducationSetting;
use App\Models\EducationSubmission;
use App\Models\User;
use App\Services\JazzCashService;
use App\Support\Education;
use App\Support\EducationLearning;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');
    $this->student = User::factory()->withoutTwoFactor()->create();
    $this->admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true, 'password' => 'Test-password-123']);
    $this->course = Course::create(['title' => 'Classroom course', 'slug' => 'classroom-course', 'category' => 'Development', 'summary' => 'Practical work', 'description' => 'Learn with assessments', 'instructor' => 'Instructor', 'duration_hours' => 10, 'fee_minor' => 10000, 'currency' => 'PKR', 'mode' => 'in_person', 'is_published' => true]);
    $this->batch = $this->course->batches()->create(['name' => 'Morning class', 'starts_on' => today(), 'ends_on' => today()->addMonth(), 'applications_close_on' => today(), 'capacity' => 20, 'schedule' => 'Monday', 'is_open' => true]);
    $this->enrollment = CourseEnrollment::create(['user_id' => $this->student->id, 'course_batch_id' => $this->batch->id, 'phone' => '03001234567', 'motivation' => 'Learn', 'fee_minor' => 10000, 'currency' => 'PKR', 'status' => 'active']);
    $this->assignment = EducationAssignment::create(['course_batch_id' => $this->batch->id, 'title' => 'Build a project', 'instructions' => 'Submit a report', 'is_published' => true, 'is_required' => true, 'pass_percent' => 60, 'allow_late' => false]);
    $this->quiz = EducationQuiz::create(['course_batch_id' => $this->batch->id, 'title' => 'Basics', 'questions' => [['prompt' => 'Choose the right answer', 'options' => ['First', 'Second'], 'correct' => 1]], 'pass_percent' => 60, 'max_attempts' => 2, 'is_published' => true, 'is_required' => true]);
    $this->base = '/admin/education/batches/'.$this->batch->id;
    $this->workspace = '/learn/'.$this->enrollment->id;
});

function learningPaymentSettings(array $overrides = []): array
{
    return $overrides + ['current_password' => 'Test-password-123', 'bank_transfer' => 'Verified receiving account', 'enabled' => 1, 'environment' => 'sandbox', 'merchant_id' => 'TEST-MERCHANT', 'jazzcash_password' => 'test-merchant-secret', 'jazzcash_integrity_salt' => 'test-integrity-secret', 'checkout_url' => 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/', 'return_url' => 'https://example.test/education/jazzcash/return'];
}

test('classroom screens restrict administrators and never expose quiz answers to students', function () {
    $this->actingAs($this->student)->get($this->base.'/classroom')->assertForbidden();
    $this->get('/admin/education/settings')->assertForbidden();
    $response = $this->get($this->workspace)->assertOk();
    $questions = $response->viewData('props')['learning']['quizzes'][0]['questions'];
    expect($questions[0])->not->toHaveKey('correct');
    $this->actingAs($this->admin)->get($this->base.'/classroom')->assertOk();
    $this->get('/admin/education/settings')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->actingAs(User::factory()->withoutTwoFactor()->create())->get($this->workspace)->assertNotFound();
    $this->enrollment->update(['status' => 'accepted']);
    $this->actingAs($this->student)->get($this->workspace)->assertViewHas('props', fn ($p) => $p['learning'] === []);
    $this->post($this->workspace.'/assignments/'.$this->assignment->id, ['body' => 'Attempt'])->assertForbidden();
    $this->post($this->workspace.'/quizzes/'.$this->quiz->id, ['answers' => [1], 'attempt_number' => 1])->assertForbidden();
});

test('attendance records validate sessions and enforce certification requirements', function () {
    $this->assignment->update(['is_required' => false]);
    $this->quiz->update(['is_required' => false]);
    $this->actingAs($this->admin)->patch($this->base.'/requirements', ['required_attendance_percent' => 75])->assertSessionHasNoErrors();
    $this->post($this->base.'/sessions', ['title' => 'Future', 'starts_at' => now()->addDay()->format('Y-m-d\TH:i'), 'status' => 'completed'])->assertSessionHasErrors('starts_at');
    $this->post($this->base.'/sessions', ['title' => 'Workshop', 'starts_at' => now()->subDay()->format('Y-m-d\TH:i'), 'status' => 'completed'])->assertSessionHasNoErrors();
    $session = EducationSession::sole();
    $review = '/admin/education/enrollments/'.$this->enrollment->id;
    $this->patch($review, ['decision' => 'complete'])->assertSessionHasErrors('status');
    $this->post($this->base.'/sessions/'.$session->id.'/attendance', ['marks' => [['enrollment_id' => $this->enrollment->id, 'status' => 'late']]])->assertSessionHasNoErrors();
    expect(EducationLearning::attendance($this->enrollment->fresh())['percent'])->toBe(100);
    $this->patch($review, ['decision' => 'complete'])->assertSessionHasNoErrors();
    $this->post($this->base.'/sessions/'.$session->id.'/attendance', ['marks' => [['enrollment_id' => $this->enrollment->id, 'status' => 'absent']]])->assertSessionHasErrors('status');
    expect($this->enrollment->fresh()->certificate_code)->not->toBeNull();
});

test('assignment submission supports private attachments revision and grade locks', function () {
    $url = $this->workspace.'/assignments/'.$this->assignment->id;
    $this->assignment->update(['due_at' => now()->subHour()]);
    $this->actingAs($this->student)->post($url, ['body' => 'Late report'])->assertSessionHasErrors('body');
    $this->assignment->update(['allow_late' => true]);
    $this->post($url, ['body' => 'My report', 'score' => 100, 'file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    $submission = EducationSubmission::sole();
    expect($submission->is_late)->toBeTrue()->and($submission->score)->toBeNull();
    $download = '/education/submissions/'.$submission->id.'/file';
    $this->get($download)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs(User::factory()->withoutTwoFactor()->create())->get($download)->assertNotFound();
    $this->actingAs($this->admin)->patch('/admin/education/submissions/'.$submission->id, ['decision' => 'grade', 'score' => 55, 'feedback' => 'Add more detail'])->assertSessionHasNoErrors();
    $this->actingAs($this->student)->post($url, ['body' => 'Overwrite'])->assertSessionHasErrors('body');
    $this->actingAs($this->admin)->patch('/admin/education/submissions/'.$submission->id, ['decision' => 'request_changes', 'feedback' => 'Please revise'])->assertSessionHasNoErrors();
    $this->actingAs($this->student)->post($url, ['body' => 'Revised report'])->assertSessionHasNoErrors();
    expect($submission->fresh()->status)->toBe('submitted')->and($submission->fresh()->file_path)->not->toBeNull();
});

test('quiz scores are server calculated attempts are idempotent and question editing locks', function () {
    $url = $this->workspace.'/quizzes/'.$this->quiz->id;
    $this->actingAs($this->student)->post($url, ['answers' => [0], 'attempt_number' => 1, 'score' => 100])->assertSessionHasNoErrors();
    expect(EducationQuizAttempt::sole()->score)->toBe(0);
    $this->post($url, ['answers' => [1], 'attempt_number' => 1])->assertSessionHasNoErrors();
    expect(EducationQuizAttempt::count())->toBe(1)->and(EducationQuizAttempt::sole()->score)->toBe(0);
    $this->post($url, ['answers' => [0], 'attempt_number' => 2])->assertSessionHasNoErrors();
    $this->post($url, ['answers' => [1], 'attempt_number' => 3])->assertSessionHasErrors('answers');
    $payload = $this->quiz->only(['title', 'questions', 'pass_percent', 'max_attempts', 'is_required', 'is_published']);
    $payload['questions'][0]['correct'] = 0;
    $this->actingAs($this->admin)->put($this->base.'/quizzes/'.$this->quiz->id, $payload)->assertSessionHasErrors('questions');
    expect($this->quiz->fresh()->questions[0]['correct'])->toBe(1);
});

test('required assignments and quizzes must pass before certification', function () {
    $review = '/admin/education/enrollments/'.$this->enrollment->id;
    $this->actingAs($this->admin)->patch($review, ['decision' => 'complete'])->assertSessionHasErrors('status');
    $this->actingAs($this->student)->post($this->workspace.'/assignments/'.$this->assignment->id, ['body' => 'Finished project'])->assertSessionHasNoErrors();
    $submission = EducationSubmission::sole();
    $this->actingAs($this->admin)->patch('/admin/education/submissions/'.$submission->id, ['decision' => 'grade', 'score' => 80, 'feedback' => 'Good work'])->assertSessionHasNoErrors();
    $this->patch($review, ['decision' => 'complete'])->assertSessionHasErrors('status');
    $this->actingAs($this->student)->post($this->workspace.'/quizzes/'.$this->quiz->id, ['answers' => [1], 'attempt_number' => 1])->assertSessionHasNoErrors();
    $this->actingAs($this->admin)->patch($review, ['decision' => 'complete'])->assertSessionHasNoErrors();
    expect($this->enrollment->fresh()->status)->toBe('completed');
});

test('cross batch and draft assessments cannot be submitted', function () {
    $this->assignment->update(['is_published' => false]);
    $this->actingAs($this->student)->post($this->workspace.'/assignments/'.$this->assignment->id, ['body' => 'Hidden'])->assertNotFound();
    $other = $this->course->batches()->create(['name' => 'Other', 'starts_on' => today(), 'ends_on' => today()->addMonth(), 'applications_close_on' => today(), 'capacity' => 5, 'schedule' => 'Friday']);
    $this->quiz->update(['course_batch_id' => $other->id]);
    $this->post($this->workspace.'/quizzes/'.$this->quiz->id, ['answers' => [1], 'attempt_number' => 1])->assertNotFound();
    $this->actingAs($this->admin)->put($this->base.'/quizzes/'.$this->quiz->id, [])->assertNotFound();
});

test('payment settings encrypt secrets and preserve blank credentials without exposing old input', function () {
    $this->actingAs($this->admin)->put('/admin/education/settings', learningPaymentSettings(['current_password' => 'wrong']))->assertSessionHasErrors('current_password');
    expect(session()->getOldInput('jazzcash_password'))->toBeNull()->and(session()->getOldInput('jazzcash_integrity_salt'))->toBeNull();
    $this->put('/admin/education/settings', learningPaymentSettings())->assertSessionHasNoErrors();
    $stored = DB::table('education_settings')->value('gateway');
    expect($stored)->not->toContain('test-merchant-secret')->not->toContain('test-integrity-secret');
    expect(app(JazzCashService::class)->configured())->toBeTrue();
    $this->get('/admin/education/settings')->assertOk()->assertDontSee('test-merchant-secret')->assertDontSee('test-integrity-secret');
    $this->put('/admin/education/settings', learningPaymentSettings(['jazzcash_password' => '', 'jazzcash_integrity_salt' => '']))->assertSessionHasNoErrors();
    expect(EducationSetting::gatewayConfig()['password'])->toBe('test-merchant-secret');
    expect(Education::methods()['bank_transfer']['instructions'])->toBe('Verified receiving account');
});

test('pending gateway payments prevent credential rotation but allow manual instruction changes', function () {
    $this->actingAs($this->admin)->put('/admin/education/settings', learningPaymentSettings())->assertSessionHasNoErrors();
    $this->enrollment->payments()->create(['method' => 'jazzcash_online', 'reference' => 'TPENDING', 'amount_minor' => 10000, 'currency' => 'PKR', 'status' => 'pending', 'gateway_environment' => 'sandbox']);
    $this->put('/admin/education/settings', learningPaymentSettings(['bank_transfer' => 'Updated instructions']))->assertSessionHasNoErrors();
    $this->put('/admin/education/settings', learningPaymentSettings(['merchant_id' => 'ANOTHER']))->assertSessionHasErrors('merchant_id');
    expect(EducationSetting::gatewayConfig()['merchant_id'])->toBe('TEST-MERCHANT');
    $this->put('/admin/education/settings', learningPaymentSettings(['enabled' => 0]))->assertSessionHasNoErrors();
    expect(app(JazzCashService::class)->configured())->toBeFalse();
});

test('payment settings reject unrelated gateways and incomplete enabled configuration', function () {
    $this->actingAs($this->admin)->put('/admin/education/settings', learningPaymentSettings(['checkout_url' => 'https://unrelated.test/pay']))->assertSessionHasErrors('checkout_url');
    $this->put('/admin/education/settings', learningPaymentSettings(['return_url' => 'https://example.test/wrong']))->assertSessionHasErrors('return_url');
    $this->put('/admin/education/settings', learningPaymentSettings(['merchant_id' => '']))->assertSessionHasErrors('enabled');
});

test('student overview counts owned courses and lists only their upcoming classes', function () {
    EducationSession::create(['course_batch_id' => $this->batch->id, 'title' => 'Next workshop', 'starts_at' => now()->addDay(), 'status' => 'scheduled']);
    $this->actingAs($this->student)->get('/learn')->assertOk()->assertViewHas('props', fn ($p) => $p['stats']['active'] === 1 && count($p['nextClasses']) === 1 && $p['nextClasses'][0]['enrollment_id'] === $this->enrollment->id);
    $this->actingAs(User::factory()->withoutTwoFactor()->create())->get('/learn')->assertOk()->assertViewHas('props', fn ($p) => count($p['nextClasses']) === 0 && count($p['enrollments']['data']) === 0);
});
