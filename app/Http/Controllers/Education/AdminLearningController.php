<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\CourseBatch;
use App\Models\EducationAssignment;
use App\Models\EducationAttendance;
use App\Models\EducationQuiz;
use App\Models\EducationSession;
use App\Models\EducationSubmission;
use App\Models\CourseEnrollment;
use App\Support\Education;
use App\Support\EducationInsights;
use App\Support\SiteNotifications;
use App\Support\ReactPage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLearningController extends Controller
{
    public function show(CourseBatch $batch)
    {
        $batch->load('course');
        $students = $batch->enrollments()->whereIn('status', ['active', 'completed'])->with('student:id,name')->orderBy('id')->get();
        $sessions = EducationSession::where('course_batch_id', $batch->id)->with('attendance')->orderByDesc('starts_at')->get();
        $assignments = EducationAssignment::where('course_batch_id', $batch->id)->with(['submissions.enrollment.student:id,name'])->latest()->get();
        $quizzes = EducationQuiz::where('course_batch_id', $batch->id)->withCount('attempts')->latest()->get();

        $announcements = DB::table('education_announcements')->where('course_batch_id', $batch->id)->latest()->limit(20)->get();
        $insights = EducationInsights::forBatch($batch)->map(fn ($row) => EducationInsights::public($row))->sortByDesc('score')->values();
        $nudged = DB::table('education_reminders')->whereIn('course_enrollment_id', $students->modelKeys())->where('key', 'like', 'nudge:%')
            ->selectRaw('course_enrollment_id, max(sent_at) as sent_at')->groupBy('course_enrollment_id')->pluck('sent_at', 'course_enrollment_id');

        return ReactPage::render('admin.education.classroom', compact('batch', 'students', 'sessions', 'assignments', 'quizzes', 'announcements', 'insights', 'nudged'));
    }

    /** "My classes": every batch the user teaches that is running, upcoming or recently finished. */
    public function teaching(Request $request)
    {
        $ids = Education::teachingBatchIds($request->user());
        $batches = CourseBatch::with('course:id,title,slug,cover_image,visual_theme')
            ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->where('ends_on', '>=', now()->subDays(30)->toDateString())
            ->withCount(['enrollments as active_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('starts_on')->limit(50)->get();
        $rows = $batches->map(function (CourseBatch $batch) {
            $insights = EducationInsights::forBatch($batch);
            $next = EducationSession::where('course_batch_id', $batch->id)->where('status', 'scheduled')->where('starts_at', '>=', now())->orderBy('starts_at')->first(['id', 'title', 'starts_at']);

            return $batch->only(['id', 'name', 'starts_on', 'ends_on', 'schedule', 'active_count']) + [
                'course' => $batch->course->only(['id', 'title', 'cover_image', 'visual_theme']),
                'at_risk' => $insights->where('level', 'at_risk')->count(),
                'watch' => $insights->where('level', 'watch')->count(),
                'average_progress' => $insights->where('status', 'active')->avg('progress') !== null ? (int) round($insights->where('status', 'active')->avg('progress')) : null,
                'to_grade' => EducationSubmission::where('status', 'submitted')->whereHas('assignment', fn ($q) => $q->where('course_batch_id', $batch->id))->count(),
                'unmarked_sessions' => EducationSession::where('course_batch_id', $batch->id)->where('status', 'scheduled')->where('starts_at', '<', now())->count(),
                'next_session' => $next,
                'needs_attention' => $insights->whereIn('level', ['at_risk', 'watch'])->take(3)->map(fn ($row) => ['name' => $row['student']['name'] ?? 'Student', 'level' => $row['level'], 'reason' => $row['reasons'][0] ?? null])->values(),
            ];
        });

        return ReactPage::render('admin.education.teaching', ['batches' => $rows, 'managesAll' => $ids === null]);
    }

    /** One-click reminder email to a student, at most once per day per student. */
    public function nudge(Request $request, CourseBatch $batch, CourseEnrollment $enrollment)
    {
        abort_unless($enrollment->course_batch_id === $batch->id && $enrollment->status === 'active', 404);
        $data = $request->validate(['message' => 'nullable|string|max:1000']);
        $key = 'nudge:'.$enrollment->id.':'.now(EducationInsights::TZ)->toDateString();
        Education::ensure(DB::table('education_reminders')->insertOrIgnore(['key' => $key, 'course_enrollment_id' => $enrollment->id, 'sent_at' => now()]) === 1, 'A reminder was already sent to this student today.', 'message');
        $insight = EducationInsights::forEnrollment($enrollment);
        SiteNotifications::educationNudge($enrollment, $insight['reasons'], $data['message'] ?? null, $request->user()->name);

        return back()->with('success', 'Reminder emailed to '.$enrollment->student->name.'.');
    }

    /** Gradebook export: one row per student with progress, attendance, every assignment and quiz. */
    public function gradebook(CourseBatch $batch)
    {
        $insights = EducationInsights::forBatch($batch);
        $assignments = EducationAssignment::where('course_batch_id', $batch->id)->where('is_published', true)->orderBy('due_at')->get(['id', 'title']);
        $quizzes = EducationQuiz::where('course_batch_id', $batch->id)->where('is_published', true)->get(['id', 'title']);
        $subs = EducationSubmission::whereIn('education_assignment_id', $assignments->modelKeys())->get()->groupBy('course_enrollment_id');
        $attempts = \App\Models\EducationQuizAttempt::whereIn('education_quiz_id', $quizzes->modelKeys())->get()->groupBy('course_enrollment_id');
        $name = str($batch->course->title.' '.$batch->name)->slug().'-gradebook.csv';

        return response()->streamDownload(function () use ($insights, $assignments, $quizzes, $subs, $attempts) {
            $out = fopen('php://output', 'w');
            // Spreadsheet apps treat cells starting with = + - @ as formulas.
            $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;
            fputcsv($out, array_merge(['Student', 'Email', 'Status', 'Insight', 'Lessons done', 'Progress %', 'Attendance %', 'Average score'],
                $assignments->map(fn ($a) => 'Assignment: '.$a->title)->all(), $quizzes->map(fn ($q) => 'Quiz (best %): '.$q->title)->all()));
            foreach ($insights as $id => $row) {
                $mySubs = collect($subs[$id] ?? [])->keyBy('education_assignment_id');
                $myAttempts = collect($attempts[$id] ?? [])->groupBy('education_quiz_id');
                fputcsv($out, array_map($safe, array_merge([
                    $row['student']['name'] ?? '', $row['student']['email'] ?? '', $row['status'], str_replace('_', ' ', $row['level']),
                    $row['lessons_done'].'/'.$row['lessons_total'], $row['progress'], $row['attendance'], $row['average_score'],
                ], $assignments->map(fn ($a) => ($s = $mySubs[$a->id] ?? null) ? ($s->status === 'graded' ? $s->score : str_replace('_', ' ', $s->status)) : 'missing')->all(),
                    $quizzes->map(fn ($q) => collect($myAttempts[$q->id] ?? [])->max('score') ?? '')->all())));
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function requirements(Request $request, CourseBatch $batch)
    {
        $data = $request->validate(['required_attendance_percent' => 'required|integer|min:0|max:100']);
        DB::transaction(fn () => CourseBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail()->update($data));

        return back()->with('success', 'Attendance requirement saved.');
    }

    public function session(Request $request, CourseBatch $batch, ?EducationSession $session = null)
    {
        abort_if($session && $session->course_batch_id !== $batch->id, 404);
        $data = $request->validate(['title' => 'required|string|max:255', 'starts_at' => 'required|date_format:Y-m-d\TH:i', 'notes' => 'nullable|string|max:5000', 'status' => 'required|in:scheduled,completed,cancelled']);
        $data['starts_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['starts_at'], 'Asia/Karachi')->utc();
        Education::ensure($data['status'] !== 'completed' || $data['starts_at']->lte(now()), 'A future session cannot be completed.', 'starts_at');
        DB::transaction(function () use ($session, $batch, $data) {
            CourseBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($session) {
                $session->update($data);
            } else {
                EducationSession::create($data + ['course_batch_id' => $batch->id]);
            }
        });

        return back()->with('success', 'Class session saved.');
    }

    public function attendance(Request $request, CourseBatch $batch, EducationSession $session)
    {
        abort_unless($session->course_batch_id === $batch->id, 404);
        $data = $request->validate(['marks' => 'required|array|min:1|max:500', 'marks.*.enrollment_id' => 'required|integer|distinct', 'marks.*.status' => 'required|in:present,absent,late,excused', 'marks.*.note' => 'nullable|string|max:500']);
        DB::transaction(function () use ($batch, $session, $data, $request) {
            CourseBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $session = EducationSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            Education::ensure($session->status === 'completed' && $session->starts_at->lte(now()), 'Complete the class session before recording attendance.');
            foreach ($data['marks'] as $mark) {
                $student = $batch->enrollments()->whereKey($mark['enrollment_id'])->firstOrFail();
                Education::ensure($student->status === 'active', 'Attendance can only be changed for active students.');
                EducationAttendance::updateOrCreate(['education_session_id' => $session->id, 'course_enrollment_id' => $student->id], ['status' => $mark['status'], 'note' => $mark['note'] ?? null, 'marked_by' => $request->user()->id]);
            }
        });

        return back()->with('success', 'Attendance saved.');
    }

    public function assignment(Request $request, CourseBatch $batch, ?EducationAssignment $assignment = null)
    {
        abort_if($assignment && $assignment->course_batch_id !== $batch->id, 404);
        $data = $request->validate(['title' => 'required|string|max:255', 'instructions' => 'required|string|max:20000', 'due_at' => 'nullable|date_format:Y-m-d\TH:i', 'allow_late' => 'required|boolean', 'is_required' => 'required|boolean', 'is_published' => 'required|boolean', 'pass_percent' => 'required|integer|min:1|max:100']);
        $data['due_at'] = filled($data['due_at'] ?? null) ? Carbon::createFromFormat('Y-m-d\TH:i', $data['due_at'], 'Asia/Karachi')->utc() : null;
        DB::transaction(function () use ($batch, $assignment, $data) {
            CourseBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($assignment) {
                $assignment = EducationAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
                Education::ensure(! $assignment->submissions()->exists() || (int) $data['pass_percent'] === (int) $assignment->pass_percent, 'The pass mark cannot change after students submit work.', 'pass_percent');
                $assignment->update($data);
            } else {
                EducationAssignment::create($data + ['course_batch_id' => $batch->id]);
            }
        });

        return back()->with('success', 'Assignment saved.');
    }

    public function grade(Request $request, EducationSubmission $submission)
    {
        $data = $request->validate(['decision' => 'required|in:grade,request_changes', 'score' => 'nullable|required_if:decision,grade|integer|min:0|max:100', 'feedback' => 'required|string|max:10000']);
        Education::locked($submission->enrollment, function ($enrollment) use ($submission, $request, $data) {
            Education::ensure($enrollment->status === 'active', 'Completed enrollments have locked grades.');
            $submission = EducationSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $submission->update(['status' => $data['decision'] === 'grade' ? 'graded' : 'changes_requested', 'score' => $data['decision'] === 'grade' ? $data['score'] : null, 'feedback' => $data['feedback'], 'graded_by' => $request->user()->id, 'graded_at' => now()]);
        });

        return back()->with('success', 'Assignment review saved.');
    }

    public function quiz(Request $request, CourseBatch $batch, ?EducationQuiz $quiz = null)
    {
        abort_if($quiz && $quiz->course_batch_id !== $batch->id, 404);
        $data = $request->validate(['title' => 'required|string|max:255', 'instructions' => 'nullable|string|max:10000', 'pass_percent' => 'required|integer|min:1|max:100', 'max_attempts' => 'required|integer|min:1|max:10', 'is_required' => 'required|boolean', 'is_published' => 'required|boolean', 'questions' => 'required|array|min:1|max:50', 'questions.*.prompt' => 'required|string|max:2000', 'questions.*.options' => 'required|array|min:2|max:6', 'questions.*.options.*' => 'required|string|max:1000', 'questions.*.correct' => 'required|integer|min:0|max:5']);
        Education::ensure(array_is_list($data['questions']), 'Use a numbered list of questions.', 'questions');
        $data['questions'] = array_values(array_map(function ($q) {
            Education::ensure(array_is_list($q['options']), 'Use a numbered list of options.', 'questions');
            Education::ensure(isset($q['options'][(int) $q['correct']]), 'Each correct answer must match an available option.', 'questions');

            return ['prompt' => $q['prompt'], 'options' => array_values($q['options']), 'correct' => (int) $q['correct']];
        }, $data['questions']));
        DB::transaction(function () use ($batch, $quiz, $data) {
            CourseBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($quiz) {
                $quiz = EducationQuiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
                $quiz->fill($data);
                Education::ensure(! $quiz->attempts()->exists() || ! $quiz->isDirty(['questions', 'pass_percent', 'max_attempts']), 'Questions, pass marks and attempt limits are locked after the first attempt. Create a new quiz to change them.', 'questions');
                $quiz->save();
            } else {
                EducationQuiz::create($data + ['course_batch_id' => $batch->id]);
            }
        });

        return back()->with('success', 'Quiz saved.');
    }
}
