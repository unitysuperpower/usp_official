<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\EducationAssignment;
use App\Models\EducationQuiz;
use App\Models\EducationQuizAttempt;
use App\Models\EducationSubmission;
use App\Support\Education;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LearningController extends Controller
{
    public function submit(Request $request, CourseEnrollment $enrollment, EducationAssignment $assignment)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        $data = $request->validate(['body' => 'required|string|max:20000', 'file' => 'nullable|file|mimes:pdf,txt,zip,docx,jpg,jpeg,png|max:10240']);
        $path = null;
        $oldPath = null;
        try {
            Education::locked($enrollment, function ($item, $batch) use ($assignment, $request, $data, &$path, &$oldPath) {
                abort_unless($item->status === 'active', 403);
                $assignment = EducationAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
                abort_unless($assignment->course_batch_id === $batch->id && $assignment->is_published, 404);
                $late = $assignment->due_at?->isPast() ?? false;
                Education::ensure(! $late || $assignment->allow_late, 'The submission deadline has passed.', 'body');
                $submission = $assignment->submissions()->where('course_enrollment_id', $item->id)->first();
                Education::ensure(! $submission || in_array($submission->status, ['submitted', 'changes_requested']), 'This assignment has been graded. Ask your instructor to request a revision before resubmitting.', 'body');
                $values = ['body' => $data['body'], 'status' => 'submitted', 'score' => null, 'submitted_at' => now(), 'graded_at' => null, 'graded_by' => null, 'is_late' => $late];
                if ($request->hasFile('file')) {
                    $path = $request->file('file')->store('education/assignments', 'local');
                    abort_unless($path, 503, 'Could not store the assignment file.');
                    $values['file_path'] = $path;
                    $values['file_name'] = mb_substr(basename(str_replace('\\', '/', $request->file('file')->getClientOriginalName())), 0, 240);
                    $oldPath = $submission?->file_path;
                }
                $assignment->submissions()->updateOrCreate(['course_enrollment_id' => $item->id], $values);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        if ($oldPath) Storage::disk('local')->delete($oldPath);

        return back()->with('success', 'Assignment submitted. Your instructor will review it.');
    }

    public function file(Request $request, EducationSubmission $submission)
    {
        abort_unless($request->user()->is_admin || $submission->enrollment->user_id === $request->user()->id, 404);
        abort_unless($submission->file_path && Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->file_name, ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function quiz(Request $request, CourseEnrollment $enrollment, EducationQuiz $quiz)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        $data = $request->validate(['answers' => 'required|array|max:50', 'answers.*' => 'required|integer|min:0|max:5', 'attempt_number' => 'required|integer|min:1|max:10']);
        Education::locked($enrollment, function ($item, $batch) use ($quiz, $data) {
            abort_unless($item->status === 'active', 403);
            $quiz = EducationQuiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            abort_unless($quiz->course_batch_id === $batch->id && $quiz->is_published, 404);
            $attempts = $quiz->attempts()->where('course_enrollment_id', $item->id)->get();
            // Browser retries of an already recorded attempt do not consume a new attempt.
            if ($attempts->contains('attempt_number', (int) $data['attempt_number'])) return;
            Education::ensure(! $attempts->contains('passed', true), 'You have already passed this quiz.', 'answers');
            Education::ensure($attempts->count() < $quiz->max_attempts && (int) $data['attempt_number'] === $attempts->count() + 1, 'No quiz attempts remain, or this form is out of date. Refresh the page.', 'answers');
            $questions = $quiz->questions;
            Education::ensure(count($data['answers']) === count($questions) && array_keys($data['answers']) === range(0, count($questions) - 1), 'Answer every question.', 'answers');
            $correct = 0;
            foreach ($questions as $index => $question) {
                Education::ensure(isset($question['options'][(int) $data['answers'][$index]]), 'Choose a valid answer for every question.', 'answers');
                if ((int) $data['answers'][$index] === (int) $question['correct']) $correct++;
            }
            $score = (int) floor($correct * 100 / count($questions));
            EducationQuizAttempt::create(['education_quiz_id' => $quiz->id, 'course_enrollment_id' => $item->id, 'answers' => $data['answers'], 'score' => $score, 'passed' => $score >= $quiz->pass_percent, 'attempt_number' => $data['attempt_number']]);
        });

        return back()->with('success', 'Quiz submitted. Your result is shown below.');
    }
}
