<?php

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\User;
use App\Services\JazzCashService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');
    $this->student = User::factory()->withoutTwoFactor()->create();
    $this->educationAdmin = User::factory()->withoutTwoFactor()->create(['is_admin' => true]);
    $this->course = Course::create(['title' => 'Practical Laravel', 'slug' => 'practical-laravel', 'category' => 'Development', 'summary' => 'Build real applications', 'description' => 'A complete course', 'instructor' => 'Instructor', 'duration_hours' => 20, 'fee_minor' => 150000, 'currency' => 'PKR', 'is_published' => true]);
    $this->batch = $this->course->batches()->create(['name' => 'Evening batch', 'starts_on' => today()->addDays(10), 'ends_on' => today()->addDays(40), 'applications_close_on' => today()->addDays(5), 'capacity' => 1, 'schedule' => 'Mon, 6 PM PKT', 'meeting_url' => 'https://example.test/private-class', 'is_open' => true]);
    $this->lesson = $this->course->lessons()->create(['title' => 'First lesson', 'content' => 'PRIVATE_LESSON_CONTENT', 'is_published' => true]);
    config(['education.payment_methods.bank_transfer.instructions' => 'Verified test account', 'jazzcash.enabled' => true, 'jazzcash.environment' => 'sandbox', 'jazzcash.merchant_id' => 'MER123', 'jazzcash.password' => 'TEST-PASSWORD', 'jazzcash.integrity_salt' => '0F5DD14AE2', 'jazzcash.checkout_url' => 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/', 'jazzcash.return_url' => 'https://example.test/education/jazzcash/return']);
});

function educationEnrollment($test, array $extra = []): CourseEnrollment
{
    return CourseEnrollment::create($extra + ['user_id' => $test->student->id, 'course_batch_id' => $test->batch->id, 'phone' => '03001234567', 'motivation' => 'Learn Laravel', 'fee_minor' => 150000, 'currency' => 'PKR', 'status' => 'accepted']);
}

function jazzCashResponse(CoursePayment $payment, array $extra = []): array
{
    $data = $extra + ['pp_MerchantID' => 'MER123', 'pp_TxnRefNo' => $payment->reference, 'pp_Amount' => (string) $payment->amount_minor, 'pp_TxnCurrency' => 'PKR', 'pp_BillReference' => 'EDU'.$payment->course_enrollment_id, 'pp_ResponseCode' => '000', 'pp_RetreivalReferenceNo' => 'RECEIPT123'];
    $data['pp_SecureHash'] = app(JazzCashService::class)->hash($data);

    return $data;
}

test('course discovery hides drafts and private lesson and meeting details', function () {
    $this->get('/courses')->assertOk()->assertViewHas('page', 'education.catalog')->assertSee('Practical Laravel');
    $this->get('/courses/practical-laravel')->assertOk()->assertDontSee('PRIVATE_LESSON_CONTENT')->assertDontSee('private-class');
    $this->course->update(['is_published' => false]);
    $this->get('/courses/practical-laravel')->assertNotFound();
    $this->get('/courses')->assertDontSee('Practical Laravel');
    $this->get('/courses?page=999')->assertNotFound();
});

test('course sitemap includes published courses and filtered catalog is noindex', function () {
    config(['seo.indexable' => true]);
    $this->get('/sitemap-courses-1.xml')->assertOk()->assertSee('practical-laravel');
    $this->get('/courses?search=Laravel')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow');
    $this->course->update(['is_published' => false]);
    $this->get('/sitemap-courses-1.xml')->assertNotFound();
});

test('application is idempotent and uses the server fee snapshot', function () {
    $url = '/education/batches/'.$this->batch->id.'/apply';
    $data = ['phone' => '03001234567', 'motivation' => 'I want to learn', 'fee_minor' => 1, 'status' => 'active'];
    $this->actingAs($this->student)->post($url, $data)->assertSessionHasNoErrors()->assertRedirect();
    $this->post($url, $data)->assertSessionHasNoErrors();
    expect(CourseEnrollment::count())->toBe(1);
    $enrollment = CourseEnrollment::sole();
    expect($enrollment->fee_minor)->toBe(150000)->and($enrollment->status)->toBe('applied');
    $this->course->update(['fee_minor' => 990000]);
    expect($enrollment->refresh()->fee_minor)->toBe(150000);
});

test('closed batches and unpublished courses reject new applications', function () {
    $this->batch->update(['is_open' => false]);
    $this->actingAs($this->student)->post('/education/batches/'.$this->batch->id.'/apply', ['phone' => '123', 'motivation' => 'Learn'])->assertSessionHasErrors('status');
    $this->batch->update(['is_open' => true]);
    $this->course->update(['is_published' => false]);
    $this->post('/education/batches/'.$this->batch->id.'/apply', ['phone' => '123', 'motivation' => 'Learn'])->assertNotFound();
});

test('acceptance respects capacity and cannot be replayed to change a decision', function () {
    $first = educationEnrollment($this, ['status' => 'applied']);
    $other = educationEnrollment($this, ['user_id' => User::factory()->create()->id, 'status' => 'applied']);
    $this->actingAs($this->educationAdmin)->patch('/admin/education/enrollments/'.$first->id, ['decision' => 'accept'])->assertSessionHasNoErrors();
    $this->patch('/admin/education/enrollments/'.$other->id, ['decision' => 'accept'])->assertSessionHasErrors('status');
    $this->patch('/admin/education/enrollments/'.$first->id, ['decision' => 'reject', 'review_note' => 'Changed mind'])->assertSessionHasErrors('status');
    expect($first->refresh()->status)->toBe('accepted');
});

test('free course acceptance activates enrollment without payment', function () {
    $enrollment = educationEnrollment($this, ['status' => 'applied', 'fee_minor' => 0]);
    $this->actingAs($this->educationAdmin)->patch('/admin/education/enrollments/'.$enrollment->id, ['decision' => 'accept'])->assertSessionHasNoErrors();
    expect($enrollment->refresh()->status)->toBe('active');
    $this->actingAs($this->student)->post('/learn/'.$enrollment->id.'/checkout')->assertSessionHasErrors('status');
});

test('students cannot read or mutate another students education records', function () {
    $enrollment = educationEnrollment($this);
    $this->actingAs(User::factory()->create());
    $this->get('/learn/'.$enrollment->id)->assertNotFound();
    $this->post('/learn/'.$enrollment->id.'/cancel')->assertNotFound();
    $this->post('/learn/'.$enrollment->id.'/checkout')->assertNotFound();
    $this->post('/learn/'.$enrollment->id.'/lessons/'.$this->lesson->id.'/complete')->assertNotFound();
    $this->get('/admin/education/courses')->assertForbidden();
    $this->patch('/admin/education/enrollments/'.$enrollment->id, ['decision' => 'accept'])->assertForbidden();
});

test('manual receipt remains private and unlocks lessons only after administrator verification', function () {
    $enrollment = educationEnrollment($this);
    $this->actingAs($this->student)->get('/learn/'.$enrollment->id)->assertOk()->assertDontSee('PRIVATE_LESSON_CONTENT')->assertDontSee('private-class');
    $url = '/learn/'.$enrollment->id.'/payments';
    $this->post($url, ['method' => 'bank_transfer', 'reference' => 'BANK001', 'proof' => UploadedFile::fake()->image('receipt.png'), 'amount_minor' => 1])->assertSessionHasNoErrors();
    $payment = CoursePayment::sole();
    expect($payment->amount_minor)->toBe(150000)->and($enrollment->refresh()->status)->toBe('accepted');
    $this->get('/education/payments/'.$payment->id.'/proof')->assertOk();
    $this->post($url, ['method' => 'bank_transfer', 'reference' => 'BANK002', 'proof' => UploadedFile::fake()->image('second.png')])->assertSessionHasErrors('status');
    $this->post('/learn/'.$enrollment->id.'/cancel')->assertSessionHasErrors('status');
    $this->actingAs(User::factory()->create())->get('/education/payments/'.$payment->id.'/proof')->assertNotFound();
    $this->actingAs($this->educationAdmin)->patch('/admin/education/payments/'.$payment->id, ['decision' => 'approve'])->assertSessionHasNoErrors();
    $this->patch('/admin/education/payments/'.$payment->id, ['decision' => 'approve'])->assertSessionHasErrors('status');
    $this->actingAs($this->student)->get('/learn/'.$enrollment->id)->assertOk()->assertSee('PRIVATE_LESSON_CONTENT')->assertSee('private-class');
    expect($enrollment->refresh()->status)->toBe('active');
});

test('course completion requires progress and issues a protected stable certificate', function () {
    $enrollment = educationEnrollment($this, ['status' => 'active']);
    $this->actingAs($this->educationAdmin)->patch('/admin/education/enrollments/'.$enrollment->id, ['decision' => 'complete'])->assertSessionHasErrors('status');
    $this->actingAs($this->student)->post('/learn/'.$enrollment->id.'/lessons/'.$this->lesson->id.'/complete')->assertSessionHasNoErrors();
    $this->actingAs($this->educationAdmin)->patch('/admin/education/enrollments/'.$enrollment->id, ['decision' => 'complete'])->assertSessionHasNoErrors();
    $enrollment->refresh();
    expect($enrollment->certificate_code)->not->toBeNull();
    $this->student->update(['name' => 'New profile name']);
    $this->actingAs($this->student)->get('/learn/'.$enrollment->id.'/certificate')->assertOk()->assertSee($enrollment->certificate_name)->assertDontSee('New profile name');
    $this->actingAs(User::factory()->create())->get('/learn/'.$enrollment->id.'/certificate')->assertNotFound();
});

test('unpaid students cannot mark lessons complete and unpublished lessons stay private', function () {
    $enrollment = educationEnrollment($this);
    $this->actingAs($this->student)->post('/learn/'.$enrollment->id.'/lessons/'.$this->lesson->id.'/complete')->assertForbidden();
    $enrollment->update(['status' => 'active']);
    $this->lesson->update(['is_published' => false]);
    $this->get('/learn/'.$enrollment->id)->assertDontSee('PRIVATE_LESSON_CONTENT');
    $this->post('/learn/'.$enrollment->id.'/lessons/'.$this->lesson->id.'/complete')->assertNotFound();
});

test('gateway checkout uses stored amount and reuses pending attempts without exposing salt', function () {
    $enrollment = educationEnrollment($this);
    $url = '/learn/'.$enrollment->id.'/checkout';
    $this->actingAs($this->student)->post($url, ['amount' => 1])->assertOk()->assertSee('value="150000"', false)->assertDontSee('0F5DD14AE2');
    $this->post($url)->assertOk();
    expect(CoursePayment::count())->toBe(1);
    expect(CoursePayment::sole()->amount_minor)->toBe(150000);
});

test('unsigned or mismatched gateway responses cannot activate enrollment', function () {
    $enrollment = educationEnrollment($this);
    $this->actingAs($this->student)->post('/learn/'.$enrollment->id.'/checkout')->assertOk();
    $payment = CoursePayment::sole();
    $data = jazzCashResponse($payment);
    $data['pp_Amount'] = '1';
    $this->post('/education/jazzcash/return', $data)->assertBadRequest();
    $this->post('/education/jazzcash/return', jazzCashResponse($payment, ['pp_Amount' => '1']))->assertBadRequest();
    $this->post('/education/jazzcash/return', jazzCashResponse($payment, ['pp_MerchantID' => 'OTHER']))->assertBadRequest();
    expect($enrollment->refresh()->status)->toBe('accepted')->and($payment->refresh()->status)->toBe('pending');
});

test('signed successful callback is session independent and idempotent', function () {
    $enrollment = educationEnrollment($this);
    $this->actingAs($this->student)->post('/learn/'.$enrollment->id.'/checkout');
    $payment = CoursePayment::sole();
    auth()->logout();
    $this->post('/education/jazzcash/return', jazzCashResponse($payment))->assertOk();
    $this->post('/education/jazzcash/return', jazzCashResponse($payment))->assertOk();
    $this->post('/education/jazzcash/return', jazzCashResponse($payment, ['pp_ResponseCode' => '124']))->assertOk();
    expect($enrollment->refresh()->status)->toBe('active')->and($payment->refresh()->status)->toBe('approved');
    expect(CoursePayment::count())->toBe(1);
});

test('uncertain payment stays pending and merchant reconciliation requires evidence', function () {
    $enrollment = educationEnrollment($this);
    $this->actingAs($this->student)->post('/learn/'.$enrollment->id.'/checkout');
    $payment = CoursePayment::sole();
    $this->post('/education/jazzcash/return', jazzCashResponse($payment, ['pp_ResponseCode' => '124']))->assertOk();
    expect($payment->refresh()->status)->toBe('pending');
    $url = '/admin/education/payments/'.$payment->id;
    $this->actingAs($this->educationAdmin)->patch($url, ['decision' => 'approve'])->assertSessionHasErrors('merchant_verified');
    $this->patch($url, ['decision' => 'reject', 'merchant_verified' => true, 'review_note' => 'No payment'])->assertSessionHasErrors('status');
    $this->travel(31)->minutes();
    $this->patch($url, ['decision' => 'reject', 'merchant_verified' => true, 'review_note' => 'Portal final failure, transaction checked'])->assertSessionHasNoErrors();
    expect($payment->refresh()->status)->toBe('rejected');
});

test('late success after another verified payment is flagged instead of enrolling twice', function () {
    $enrollment = educationEnrollment($this);
    $this->actingAs($this->student)->post('/learn/'.$enrollment->id.'/checkout');
    $first = CoursePayment::sole();
    $first->update(['status' => 'rejected']);
    $this->post('/learn/'.$enrollment->id.'/checkout');
    $second = CoursePayment::latest('id')->first();
    $this->post('/education/jazzcash/return', jazzCashResponse($second))->assertOk();
    $this->post('/education/jazzcash/return', jazzCashResponse($first))->assertOk();
    expect($first->refresh()->status)->toBe('review_required')->and($second->refresh()->status)->toBe('approved');
});

test('account closure with education records reports a recoverable error without logging out', function () {
    educationEnrollment($this);
    $this->actingAs($this->student)->delete('/profile', ['password' => 'password'])->assertSessionHasErrors('password');
    $this->assertAuthenticatedAs($this->student);
    expect($this->student->fresh())->not->toBeNull();
});

test('gateway hash agrees with an independent HMAC vector and rejects malformed fields', function () {
    $expected = strtoupper(hash_hmac('sha256', '0F5DD14AE2&2995&MER123&A48cvE28', '0F5DD14AE2'));
    $gateway = app(JazzCashService::class);
    expect($gateway->hash(['pp_OrderInfo' => 'A48cvE28', 'pp_MerchantID' => 'MER123', 'pp_Amount' => '2995']))->toBe($expected);
    expect($gateway->valid(['pp_Amount' => [], 'pp_SecureHash' => str_repeat('a', 64)]))->toBeFalse();
});

test('administrators can create courses batches and lessons while preserving validation boundaries', function () {
    $this->actingAs($this->educationAdmin);
    foreach (['/admin/education/courses', '/admin/education/courses/create', '/admin/education/enrollments', '/admin/education/payments'] as $url) {
        $this->get($url)->assertOk();
    }
    $data = ['title' => 'Design course', 'slug' => 'design-course', 'category' => 'Design', 'level' => 'beginner', 'mode' => 'hybrid', 'summary' => 'Learn design', 'description' => 'Detailed course', 'instructor' => 'Teacher', 'duration_hours' => 12, 'fee' => '1200.50', 'is_published' => true];
    $this->post('/admin/education/courses', $data)->assertSessionHasNoErrors();
    $course = Course::where('slug', 'design-course')->firstOrFail();
    expect($course->fee_minor)->toBe(120050);
    $this->get('/admin/education/courses/'.$course->id.'/edit')->assertOk();
    $this->put('/admin/education/courses/'.$course->id, $data + ['extra' => 'ignored'])->assertSessionHasNoErrors();
    $this->post('/admin/education/courses', $data)->assertSessionHasErrors('slug');
    $batch = ['name' => 'New intake', 'starts_on' => today()->addDays(10)->toDateString(), 'ends_on' => today()->addDays(30)->toDateString(), 'applications_close_on' => today()->addDays(5)->toDateString(), 'capacity' => 10, 'schedule' => 'Mon at 6 PM PKT', 'is_open' => true];
    $url = '/admin/education/courses/'.$course->id.'/batches';
    $this->post($url, $batch)->assertSessionHasErrors('location');
    $this->post($url, $batch + ['location' => 'Campus'])->assertSessionHasNoErrors();
    $this->post('/admin/education/courses/'.$course->id.'/lessons', ['title' => 'Design basics', 'position' => 1, 'duration_minutes' => 15, 'content' => 'A useful lesson', 'is_published' => true])->assertSessionHasNoErrors();
    $this->put('/admin/education/courses/'.$course->id.'/lessons/'.$this->lesson->id, [])->assertNotFound();
});

test('students can correct a rejected receipt without losing the earlier review', function () {
    $enrollment = educationEnrollment($this);
    Storage::disk('local')->put('old.png', 'old proof');
    $payment = $enrollment->payments()->create(['method' => 'bank_transfer', 'reference' => 'BANK123', 'amount_minor' => 150000, 'currency' => 'PKR', 'proof_path' => 'old.png', 'status' => 'rejected', 'review_note' => 'Receipt was unreadable', 'reviewed_by' => $this->educationAdmin->id, 'reviewed_at' => now()]);
    $this->actingAs($this->student)->post('/education/payments/'.$payment->id.'/resubmit', ['proof' => UploadedFile::fake()->image('corrected.png')])->assertSessionHasNoErrors();
    expect($payment->refresh()->status)->toBe('pending')->and($payment->review_history[0]['note'])->toBe('Receipt was unreadable');
    Storage::disk('local')->assertMissing('old.png');
    Storage::disk('local')->assertExists($payment->proof_path);
    expect(CoursePayment::count())->toBe(1);
});

test('gateway checkout is unavailable without credentials and unsafe endpoint configurations', function () {
    $gateway = app(JazzCashService::class);
    expect($gateway->configured())->toBeTrue();
    config(['jazzcash.checkout_url' => 'https://untrusted.example/checkout']);
    expect($gateway->configured())->toBeFalse();
    $enrollment = educationEnrollment($this);
    $this->actingAs($this->student)->post('/learn/'.$enrollment->id.'/checkout')->assertSessionHasErrors('payment');
    expect(CoursePayment::count())->toBe(0);
});

test('administrators can record external resolution without changing enrollment or reopening on replay', function () {
    $enrollment = educationEnrollment($this, ['status' => 'active']);
    $payment = $enrollment->payments()->create(['method' => 'jazzcash_online', 'reference' => 'TRESOLVE123', 'amount_minor' => 150000, 'currency' => 'PKR', 'status' => 'review_required', 'gateway_environment' => 'sandbox', 'review_note' => 'Late duplicate receipt']);
    $url = '/admin/education/payments/'.$payment->id;
    $this->actingAs($this->student)->patch($url, ['decision' => 'resolve'])->assertForbidden();
    $this->actingAs($this->educationAdmin)->patch($url, ['decision' => 'resolve'])->assertSessionHasErrors('merchant_verified');
    $this->patch($url, ['decision' => 'resolve', 'merchant_verified' => true, 'review_note' => 'Duplicate settled in portal, reference REF123'])->assertSessionHasNoErrors();
    $this->post('/education/jazzcash/return', jazzCashResponse($payment))->assertOk();
    expect($payment->refresh()->status)->toBe('resolved')->and($enrollment->refresh()->status)->toBe('active');
});
