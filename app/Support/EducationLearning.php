<?php

namespace App\Support;

use App\Models\CourseEnrollment;
use App\Models\EducationAssignment;
use App\Models\EducationAttendance;
use App\Models\EducationQuiz;
use App\Models\EducationQuizAttempt;
use App\Models\EducationSession;
use App\Models\EducationSubmission;

class EducationLearning
{
    public static function attendance(CourseEnrollment $enrollment): array
    {
        $sessions = EducationSession::where('course_batch_id', $enrollment->course_batch_id)->where('status', 'completed')->pluck('id');
        $marks = EducationAttendance::where('course_enrollment_id', $enrollment->id)->whereIn('education_session_id', $sessions)->get();
        $counted = $sessions->count() - $marks->where('status', 'excused')->count();
        $attended = $marks->whereIn('status', ['present', 'late'])->count();

        return ['attended' => $attended, 'counted' => $counted, 'percent' => $counted ? (int) floor($attended * 100 / $counted) : null, 'required' => $enrollment->batch->required_attendance_percent];
    }

    public static function studentProps(CourseEnrollment $enrollment): array
    {
        if (! in_array($enrollment->status, ['active', 'completed'], true)) return [];
        $assignments = EducationAssignment::where('course_batch_id', $enrollment->course_batch_id)->where('is_published', true)->orderBy('due_at')->get();
        $quizzes = EducationQuiz::where('course_batch_id', $enrollment->course_batch_id)->where('is_published', true)->get();

        return [
            'sessions' => EducationSession::where('course_batch_id', $enrollment->course_batch_id)->orderBy('starts_at')->get(),
            'attendance' => EducationAttendance::where('course_enrollment_id', $enrollment->id)->get(['education_session_id', 'status', 'note']),
            'attendanceSummary' => self::attendance($enrollment),
            'assignments' => $assignments,
            'submissions' => EducationSubmission::where('course_enrollment_id', $enrollment->id)->whereIn('education_assignment_id', $assignments->modelKeys())->get(),
            // Answer keys never enter student page props, including hidden/draft questions.
            'quizzes' => $quizzes->map(fn ($quiz) => $quiz->only(['id', 'title', 'instructions', 'pass_percent', 'max_attempts', 'is_required']) + ['questions' => array_map(fn ($q) => ['prompt' => $q['prompt'], 'options' => $q['options']], $quiz->questions)]),
            'attempts' => EducationQuizAttempt::where('course_enrollment_id', $enrollment->id)->whereIn('education_quiz_id', $quizzes->modelKeys())->get(['education_quiz_id', 'score', 'passed', 'attempt_number', 'created_at']),
        ];
    }

    public static function requireCompletion(CourseEnrollment $enrollment): void
    {
        $summary = self::attendance($enrollment);
        if ($summary['required'] > 0) {
            Education::ensure($summary['percent'] !== null && $summary['percent'] >= $summary['required'], 'The required attendance percentage has not been met. Complete attendance records before issuing a certificate.');
        }
        $assignments = EducationAssignment::where('course_batch_id', $enrollment->course_batch_id)->where('is_published', true)->where('is_required', true)->get();
        foreach ($assignments as $assignment) {
            Education::ensure(EducationSubmission::where('course_enrollment_id', $enrollment->id)->where('education_assignment_id', $assignment->id)->where('status', 'graded')->where('score', '>=', $assignment->pass_percent)->exists(), 'A required assignment has not been passed: '.$assignment->title);
        }
        $quizIds = EducationQuiz::where('course_batch_id', $enrollment->course_batch_id)->where('is_published', true)->where('is_required', true)->pluck('id');
        $passed = EducationQuizAttempt::where('course_enrollment_id', $enrollment->id)->whereIn('education_quiz_id', $quizIds)->where('passed', true)->distinct()->count('education_quiz_id');
        Education::ensure($passed === $quizIds->count(), 'The student must pass all required quizzes before certification.');
    }
}
