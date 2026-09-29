<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\CourseBatch;
use App\Models\CourseEnrollment;
use App\Models\EducationAssignment;
use App\Models\EducationAttendance;
use App\Models\EducationQuiz;
use App\Models\EducationSession;
use App\Models\EducationSubmission;
use App\Support\Education;
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

        return ReactPage::render('admin.education.classroom', compact('batch', 'students', 'sessions', 'assignments', 'quizzes'));
    }

    public function requirements(Request $request, CourseBatch $batch)
    {
        $data = $request->validate(['required_attendance_percent' => 'required|integer|min:0|max:100']);
        $batch->update($data);

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
            if ($session) $session->update($data);
            else EducationSession::create($data + ['course_batch_id' => $batch->id]);
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
            } else EducationAssignment::create($data + ['course_batch_id' => $batch->id]);
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
            } else EducationQuiz::create($data + ['course_batch_id' => $batch->id]);
        });

        return back()->with('success', 'Quiz saved.');
    }
}
