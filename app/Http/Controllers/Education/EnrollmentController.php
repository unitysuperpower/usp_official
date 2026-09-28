<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CoursePayment;
use App\Support\Education;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    private function authorizeStudent(Request $request, CourseEnrollment $enrollment): void
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
    }

    public function index(Request $request)
    {
        $enrollments = CourseEnrollment::where('user_id', $request->user()->id)->with('batch.course')->latest()->paginate(12);
        foreach ($enrollments as $enrollment) {
            $enrollment->batch->makeHidden('meeting_url');
        }

        return ReactPage::render('education.enrollments', compact('enrollments'));
    }

    public function show(Request $request, CourseEnrollment $enrollment)
    {
        $this->authorizeStudent($request, $enrollment);
        $enrollment->load('batch.course', 'payments');
        $canLearn = in_array($enrollment->status, ['active', 'completed']);
        if (! $canLearn) {
            $enrollment->batch->makeHidden('meeting_url');
        }
        $lessons = $canLearn ? $enrollment->batch->course->lessons()->where('is_published', true)->get() : [];
        $completed = DB::table('course_lesson_progress')->where('course_enrollment_id', $enrollment->id)->pluck('course_lesson_id');

        return ReactPage::render('education.workspace', [
            'enrollment' => $enrollment, 'lessons' => $lessons, 'completed' => $completed,
            'paymentMethods' => Education::methods(),
        ]);
    }

    public function cancel(Request $request, CourseEnrollment $enrollment)
    {
        $this->authorizeStudent($request, $enrollment);
        Education::locked($enrollment, function ($item) {
            Education::ensure(in_array($item->status, ['applied', 'accepted']), 'Only unpaid applications can be withdrawn. Contact the education team about an enrolled course.');
            Education::ensure(! $item->payments()->whereIn('status', ['pending', 'approved'])->exists(), 'A payment is being reviewed or has been approved. Contact the education team.');
            $item->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Application withdrawn.');
    }

    public function payment(Request $request, CourseEnrollment $enrollment)
    {
        $this->authorizeStudent($request, $enrollment);
        $request->merge(['reference' => is_string($request->reference) ? strtoupper(trim($request->reference)) : $request->reference]);
        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(Education::methods()))],
            'reference' => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9][A-Z0-9 ._\/-]*$/', Rule::unique('course_payments')->where('method', $request->input('method'))],
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        $path = null;
        try {
            Education::locked($enrollment, function ($item) use ($request, $data, &$path) {
                Education::ensure($item->status === 'accepted' && $item->fee_minor > 0, 'Payment is available only after acceptance for a paid course.');
                Education::ensure(! $item->payments()->whereIn('status', ['pending', 'approved'])->exists(), 'You already have a payment awaiting review or approved.');
                $path = $request->file('proof')->store('education/payment-proofs', 'local');
                abort_unless($path, 503, 'Payment proof could not be stored.');
                $item->payments()->create(['method' => $data['method'], 'reference' => $data['reference'], 'proof_path' => $path, 'amount_minor' => $item->fee_minor, 'currency' => $item->currency]);
            });
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }

        return back()->with('success', 'Payment proof submitted. Your course unlocks after verification.');
    }

    public function proof(Request $request, CoursePayment $payment)
    {
        abort_unless($request->user()->is_admin || $payment->enrollment->user_id === $request->user()->id, 404);
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path, 'payment-'.$payment->id.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION), ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function progress(Request $request, CourseEnrollment $enrollment, CourseLesson $lesson)
    {
        $this->authorizeStudent($request, $enrollment);
        Education::locked($enrollment, function ($item, $batch) use ($lesson) {
            abort_unless(in_array($item->status, ['active', 'completed']), 403);
            abort_unless($lesson->course_id === $batch->course_id && $lesson->is_published, 404);
            DB::table('course_lesson_progress')->updateOrInsert(['course_enrollment_id' => $item->id, 'course_lesson_id' => $lesson->id], ['completed_at' => now()]);
        });

        return back()->with('success', 'Lesson marked complete.');
    }

    public function certificate(Request $request, CourseEnrollment $enrollment)
    {
        abort_unless($request->user()->is_admin || $enrollment->user_id === $request->user()->id, 404);
        abort_unless($enrollment->status === 'completed' && $enrollment->certificate_code, 404);

        return response()->view('education.certificate', compact('enrollment'))->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
