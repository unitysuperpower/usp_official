<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Support\Education;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminEnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:200', 'status' => 'nullable|in:applied,accepted,active,completed,rejected,cancelled']);
        $enrollments = CourseEnrollment::with(['student:id,name,email', 'batch.course'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->latest()->paginate(20)->withQueryString();
        $stats = ['courses' => Course::count(), 'applications_to_review' => CourseEnrollment::where('status', 'applied')->count(), 'active_students' => CourseEnrollment::where('status', 'active')->count(), 'payments_to_verify' => CoursePayment::whereIn('status', ['pending', 'review_required'])->count()];

        return ReactPage::render('admin.education.enrollments', compact('enrollments', 'stats', 'filters'));
    }

    public function show(CourseEnrollment $enrollment)
    {
        $enrollment->load(['student', 'batch.course', 'payments']);
        $lessons = $enrollment->batch->course->lessons()->where('is_published', true)->get(['id', 'title']);
        $completed = DB::table('course_lesson_progress')->where('course_enrollment_id', $enrollment->id)->pluck('course_lesson_id');

        return ReactPage::render('admin.education.review', ['enrollment' => $enrollment, 'studentEmail' => $enrollment->student->email, 'lessons' => $lessons, 'completed' => $completed, 'learning' => \App\Support\EducationLearning::studentProps($enrollment)]);
    }

    public function review(Request $request, CourseEnrollment $enrollment)
    {
        $data = $request->validate(['decision' => 'required|in:accept,reject,complete', 'review_note' => 'nullable|required_if:decision,reject|string|max:5000']);
        Education::locked($enrollment, function ($item, $batch) use ($data) {
            if ($data['decision'] === 'complete') {
                Education::ensure($item->status === 'active', 'Only active enrollments can be completed.');
                \App\Support\EducationLearning::requireCompletion($item);
                $lessonIds = $batch->course->lessons()->where('is_published', true)->pluck('id');
                if ($batch->course->mode !== 'in_person') {
                    Education::ensure($lessonIds->isNotEmpty() && DB::table('course_lesson_progress')->where('course_enrollment_id', $item->id)->whereIn('course_lesson_id', $lessonIds)->count() === $lessonIds->count(), 'The student must finish all published lessons before certification.');
                }
                $item->update(['status' => 'completed', 'completed_at' => now(), 'certificate_code' => (string) Str::uuid(), 'certificate_name' => $item->student->name, 'certificate_course' => $batch->course->title, 'review_note' => $data['review_note'] ?? null]);

                return;
            }
            Education::ensure($item->status === 'applied', 'This application has already been reviewed.');
            if ($data['decision'] === 'accept') {
                Education::ensure($batch->ends_on->gte(today()), 'This batch has ended.');
                Education::ensure($batch->enrollments()->whereIn('status', Education::RESERVED)->count() < $batch->capacity, 'This batch is full. Increase capacity or choose a different intake.');
                $item->update(['status' => $item->fee_minor === 0 ? 'active' : 'accepted', 'accepted_at' => now(), 'enrolled_at' => $item->fee_minor === 0 ? now() : null, 'review_note' => $data['review_note'] ?? null]);
            } else {
                $item->update(['status' => 'rejected', 'review_note' => $data['review_note']]);
            }
        });

        return back()->with('success', 'Enrollment updated.');
    }

    public function payments(Request $request)
    {
        $filters = $request->validate(['status' => 'nullable|in:pending,approved,rejected,review_required,resolved']);
        $payments = CoursePayment::with(['enrollment.student:id,name', 'enrollment.batch.course'])->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))->latest()->paginate(20)->withQueryString();

        return ReactPage::render('admin.education.payments', compact('payments', 'filters'));
    }

    public function verify(Request $request, CoursePayment $payment)
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject,resolve', 'review_note' => 'nullable|required_if:decision,reject|string|max:5000', 'merchant_verified' => 'nullable|boolean']);
        Education::locked($payment->enrollment, function ($enrollment) use ($payment, $data, $request) {
            $payment = CoursePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($data['decision'] === 'resolve') {
                Education::ensure($payment->status === 'review_required' && $payment->method === 'jazzcash_online', 'Only flagged gateway payments can be resolved.');
                Education::ensure($request->boolean('merchant_verified') && filled($data['review_note'] ?? null), 'Confirm the merchant portal resolution and record its reference.', 'merchant_verified');
                $history = $payment->review_history ?? [];
                $history[] = ['status' => 'review_required', 'note' => $payment->review_note, 'reviewed_at' => now()->toIso8601String()];
                $payment->update(['status' => 'resolved', 'review_note' => $data['review_note'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_history' => $history]);

                return;
            }
            Education::ensure($payment->status === 'pending', 'This payment has already been reviewed.');
            if ($payment->method === 'jazzcash_online') {
                Education::ensure($request->boolean('merchant_verified') && filled($data['review_note'] ?? null), 'Confirm the merchant portal result and enter its reference in the review note.', 'merchant_verified');
                Education::ensure($data['decision'] !== 'reject' || $payment->expires_at?->isPast(), 'Wait until this checkout expires before confirming it was not paid.');
            }
            if ($data['decision'] === 'approve') {
                Education::ensure($payment->amount_minor === $enrollment->fee_minor && $payment->currency === $enrollment->currency, 'Payment amount or currency does not match the enrollment.');
                Education::paid($enrollment);
            }
            $payment->update(['status' => $data['decision'] === 'approve' ? 'approved' : 'rejected', 'review_note' => $data['review_note'] ?? null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        });

        return back()->with('success', 'Payment verification saved.');
    }
}
