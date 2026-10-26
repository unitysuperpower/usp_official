<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CoursePayment;
use App\Models\EducationAssignment;
use App\Models\EducationSession;
use App\Services\JazzCashService;
use App\Support\Education;
use App\Support\EducationInsights;
use App\Support\EducationLearning;
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
        $filters = $request->validate(['search' => 'nullable|string|max:200', 'status' => 'nullable|in:active,accepted,completed,applied,rejected,cancelled']);
        $enrollments = CourseEnrollment::where('user_id', $request->user()->id)->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('batch.course', fn ($c) => $c->where('title', 'like', "%{$search}%")))->with(['batch.course' => fn ($q) => $q->withCount(['lessons as published_lessons_count' => fn ($q) => $q->where('is_published', true)])])->latest()->paginate(12)->withQueryString();
        $progress = DB::table('course_lesson_progress')->join('course_lessons', 'course_lessons.id', '=', 'course_lesson_progress.course_lesson_id')->whereIn('course_enrollment_id', $enrollments->modelKeys())->where('course_lessons.is_published', true)->selectRaw('course_enrollment_id, count(*) as total')->groupBy('course_enrollment_id')->pluck('total', 'course_enrollment_id');
        foreach ($enrollments as $enrollment) {
            $enrollment->completed_lessons_count = $progress[$enrollment->id] ?? 0;
            $enrollment->batch->makeHidden('meeting_url');
        }

        $stats = CourseEnrollment::where('user_id', $request->user()->id)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $active = CourseEnrollment::where('user_id', $request->user()->id)->where('status', 'active')->pluck('id', 'course_batch_id');
        $nextClasses = EducationSession::whereIn('course_batch_id', $active->keys())->where('status', 'scheduled')->where('starts_at', '>=', now())->with('batch.course:id,title')->orderBy('starts_at')->limit(5)->get()->map(fn ($session) => ['id' => $session->id, 'title' => $session->title, 'starts_at' => $session->starts_at, 'course_title' => $session->batch->course->title, 'enrollment_id' => $active[$session->course_batch_id]]);

        $deadlines = EducationAssignment::whereIn('course_batch_id', $active->keys())->where('is_published', true)->whereNotNull('due_at')->whereDoesntHave('submissions', fn ($q) => $q->whereIn('course_enrollment_id', $active->values())->whereIn('status', ['submitted', 'graded']))->with('batch.course:id,title')->orderBy('due_at')->limit(8)->get()->map(fn ($a) => ['id' => $a->id, 'title' => $a->title, 'due_at' => $a->due_at, 'course_title' => $a->batch->course->title, 'enrollment_id' => $active[$a->course_batch_id]]);

        $resume = CourseEnrollment::where('user_id', $request->user()->id)->where('status', 'active')->whereHas('batch.course.lessons', fn ($q) => $q->where('is_published', true)->whereNotExists(fn ($p) => $p->selectRaw('1')->from('course_lesson_progress')->whereColumn('course_lesson_id', 'course_lessons.id')->whereColumn('course_enrollment_id', 'course_enrollments.id')))->with('batch.course')->latest('enrolled_at')->first();
        $continueLearning = null;
        if ($resume) {
            $done = DB::table('course_lesson_progress')->where('course_enrollment_id', $resume->id)->pluck('course_lesson_id');
            $next = $resume->batch->course->lessons()->where('is_published', true)->whereNotIn('id', $done)->first();
            if ($next) {
                $continueLearning = ['enrollment_id' => $resume->id, 'title' => $resume->batch->course->title, 'lesson_title' => $next->title, 'lesson_id' => $next->id, 'duration_minutes' => $next->duration_minutes, 'cover_image' => $resume->batch->course->cover_image, 'cover_alt' => $resume->batch->course->cover_alt, 'visual_theme' => $resume->batch->course->visual_theme];
            }
        }

        return ReactPage::render('education.enrollments', compact('enrollments', 'stats', 'nextClasses', 'deadlines', 'filters', 'continueLearning'));
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
            'learning' => EducationLearning::studentProps($enrollment),
            'notes' => $canLearn ? DB::table('education_notes')->where('course_enrollment_id', $enrollment->id)->pluck('body', 'course_lesson_id') : [],
            'announcements' => $canLearn ? DB::table('education_announcements')->where('course_batch_id', $enrollment->course_batch_id)->latest()->limit(20)->get() : [],
            'jazzcashEnabled' => app(JazzCashService::class)->configured(),
            'plan' => EducationInsights::studentPlan($enrollment),
        ]);
    }

    public function cancel(Request $request, CourseEnrollment $enrollment)
    {
        $this->authorizeStudent($request, $enrollment);
        Education::locked($enrollment, function ($item) {
            Education::ensure(in_array($item->status, ['applied', 'accepted']), 'Only unpaid applications can be withdrawn. Contact the education team about an enrolled course.');
            Education::ensure(! $item->payments()->whereIn('status', ['pending', 'approved', 'review_required'])->exists(), 'A payment is being reviewed or has been approved. Contact the education team.');
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
                Education::ensure(! $item->payments()->whereIn('status', ['pending', 'approved', 'review_required'])->exists(), 'You already have a payment awaiting review or approved.');
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

    public function resubmit(Request $request, CoursePayment $payment)
    {
        $this->authorizeStudent($request, $payment->enrollment);
        $request->validate(['proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120']);
        $path = null;
        $oldPath = null;
        try {
            Education::locked($payment->enrollment, function ($enrollment) use ($payment, $request, &$path, &$oldPath) {
                $payment = CoursePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                Education::ensure($payment->method !== 'jazzcash_online' && $payment->status === 'rejected' && $enrollment->status === 'accepted', 'This payment cannot be resubmitted.');
                Education::ensure(! $enrollment->payments()->whereIn('status', ['pending', 'approved', 'review_required'])->exists(), 'Resolve your existing payment before resubmitting.');
                $path = $request->file('proof')->store('education/payment-proofs', 'local');
                abort_unless($path, 503, 'Payment proof could not be stored.');
                $oldPath = $payment->proof_path;
                $history = $payment->review_history ?? [];
                $history[] = ['status' => 'rejected', 'note' => $payment->review_note, 'reviewed_by' => $payment->reviewed_by, 'reviewed_at' => $payment->reviewed_at?->toIso8601String()];
                $payment->update(['proof_path' => $path, 'status' => 'pending', 'review_note' => null, 'reviewed_by' => null, 'reviewed_at' => null, 'review_history' => $history]);
            });
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'Corrected proof submitted for the same transaction reference.');
    }

    public function proof(Request $request, CoursePayment $payment)
    {
        abort_unless($request->user()->can('education.payments') || $payment->enrollment->user_id === $request->user()->id, 404);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path, 'payment-'.$payment->id.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION), ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function progress(Request $request, CourseEnrollment $enrollment, CourseLesson $lesson)
    {
        $this->authorizeStudent($request, $enrollment);
        Education::locked($enrollment, function ($item, $batch) use ($lesson) {
            abort_unless(in_array($item->status, ['active', 'completed']), 403);
            abort_unless($lesson->course_id === $batch->course_id && $lesson->is_published, 404);
            DB::table('course_lesson_progress')->updateOrInsert(['course_enrollment_id' => $item->id, 'course_lesson_id' => $lesson->id], ['completed_at' => now()]);
            EducationInsights::touch($item, $lesson->id);
        });
        if ($request->boolean('continue')) {
            $done = DB::table('course_lesson_progress')->where('course_enrollment_id', $enrollment->id)->pluck('course_lesson_id');
            $lessons = $enrollment->batch->course->lessons()->where('is_published', true)->get(['id']);
            $index = $lessons->search(fn ($l) => $l->id === $lesson->id);
            $next = $lessons->slice($index + 1)->first(fn ($l) => ! $done->contains($l->id)) ?? $lessons->first(fn ($l) => ! $done->contains($l->id));
            if ($next) {
                return redirect('/learn/'.$enrollment->id.'?tab=lessons&lesson='.$next->id)->with('success', 'Lesson complete. Here’s your next one.');
            }

            return redirect('/learn/'.$enrollment->id.'?tab=overview')->with('success', 'You’ve finished every lesson in this course. Great work!');
        }

        return back()->with('success', 'Lesson marked complete.');
    }

    // Called by the lesson player when a lesson is opened: resume point, streak and activity.
    public function open(Request $request, CourseEnrollment $enrollment, CourseLesson $lesson)
    {
        $this->authorizeStudent($request, $enrollment);
        abort_unless($enrollment->status === 'active', 403);
        abort_unless($lesson->course_id === $enrollment->batch->course_id && $lesson->is_published, 404);
        EducationInsights::touch($enrollment, $lesson->id);

        return response()->json(['ok' => true]);
    }

    public function certificate(Request $request, CourseEnrollment $enrollment)
    {
        abort_unless($request->user()->can('education.enrollments') || $enrollment->user_id === $request->user()->id, 404);
        abort_unless($enrollment->status === 'completed' && $enrollment->certificate_code, 404);

        return response()->view('education.certificate', compact('enrollment'))->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
