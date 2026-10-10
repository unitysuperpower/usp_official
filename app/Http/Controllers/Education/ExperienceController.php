<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseBatch;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CoursePayment;
use App\Models\EducationSubmission;
use App\Support\Education;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExperienceController extends Controller
{
    public function note(Request $request, CourseEnrollment $enrollment, CourseLesson $lesson)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        $data = $request->validate(['body' => 'nullable|string|max:20000']);
        Education::locked($enrollment, function ($item, $batch) use ($lesson, $data) {
            abort_unless(in_array($item->status, ['active', 'completed']), 403);
            abort_unless($lesson->course_id === $batch->course_id && $lesson->is_published, 404);
            $key = ['course_enrollment_id' => $item->id, 'course_lesson_id' => $lesson->id];
            if (blank($data['body'] ?? null)) {
                DB::table('education_notes')->where($key)->delete();
            } else {
                DB::table('education_notes')->upsert([$key + ['body' => $data['body'], 'created_at' => now(), 'updated_at' => now()]], ['course_enrollment_id', 'course_lesson_id'], ['body', 'updated_at']);
            }
        });

        return back()->with('success', 'Private lesson notes saved.');
    }

    public function announce(Request $request, CourseBatch $batch)
    {
        $data = $request->validate(['title' => 'required|string|max:255', 'body' => 'required|string|max:10000']);
        DB::table('education_announcements')->insert($data + ['course_batch_id' => $batch->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Announcement published to enrolled students.');
    }

    public function removeAnnouncement(CourseBatch $batch, int $announcement)
    {
        abort_unless(DB::table('education_announcements')->where('id', $announcement)->where('course_batch_id', $batch->id)->delete(), 404);

        return back()->with('success', 'Announcement removed.');
    }

    public function receipt(Request $request, CoursePayment $payment)
    {
        $payment->load('enrollment.student', 'enrollment.batch.course');
        abort_unless($request->user()->is_admin || $payment->enrollment->user_id === $request->user()->id, 404);
        abort_unless($payment->status === 'approved', 404);

        return response()->view('education.receipt', compact('payment'))->header('Cache-Control', 'private, no-store');
    }

    public function overview()
    {
        $stats = ['Published courses' => Course::where('is_published', true)->count(), 'Active students' => CourseEnrollment::where('status', 'active')->distinct()->count('user_id'), 'Applications to review' => CourseEnrollment::where('status', 'applied')->count(), 'Assignments to grade' => EducationSubmission::where('status', 'submitted')->count()];
        $payments = CoursePayment::where('status', 'approved')->selectRaw('currency, SUM(amount_minor) as collected')->groupBy('currency')->get();
        $outstanding = CourseEnrollment::where('status', 'accepted')->where('fee_minor', '>', 0)->whereDoesntHave('payments', fn ($q) => $q->where('status', 'approved'))->selectRaw('currency, SUM(fee_minor) as outstanding')->groupBy('currency')->get();
        $pendingPayments = CoursePayment::whereIn('status', ['pending', 'review_required'])->count();
        $courses = Course::withCount(['batches', 'lessons' => fn ($q) => $q->where('is_published', true)])->with(['batches' => fn ($q) => $q->withCount(['enrollments', 'enrollments as active_count' => fn ($e) => $e->where('status', 'active'), 'enrollments as completed_count' => fn ($e) => $e->where('status', 'completed')])])->latest()->paginate(12);
        $today = now('Asia/Karachi')->startOfDay();
        $start = $today->copy()->subDays(29);
        $counts = CourseEnrollment::whereBetween('created_at', [$start->copy()->utc(), $today->copy()->endOfDay()->utc()])->get(['created_at'])->groupBy(fn ($e) => $e->created_at->copy()->timezone('Asia/Karachi')->format('Y-m-d'))->map->count();
        $trend = collect(range(0, 29))->mapWithKeys(fn ($day) => [$start->copy()->addDays($day)->format('Y-m-d') => 0])->replace($counts);
        $followups = CourseEnrollment::where('status', 'active')->where('enrolled_at', '<', now()->subDays(7))->whereNotExists(fn ($q) => $q->selectRaw('1')->from('course_lesson_progress')->whereColumn('course_enrollment_id', 'course_enrollments.id'))->with('student:id,name', 'batch.course:id,title')->latest()->limit(10)->get();

        return ReactPage::render('admin.education.overview', compact('stats', 'payments', 'outstanding', 'pendingPayments', 'courses', 'trend', 'followups'));
    }
}
