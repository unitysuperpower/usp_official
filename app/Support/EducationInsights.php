<?php

namespace App\Support;

use App\Models\CourseBatch;
use App\Models\CourseEnrollment;
use App\Models\EducationAssignment;
use App\Models\EducationAttendance;
use App\Models\EducationQuiz;
use App\Models\EducationQuizAttempt;
use App\Models\EducationSession;
use App\Models\EducationSubmission;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Smart" learning signals computed from data the LMS already records: pace against the batch
 * timeline, attendance, missing work, quiz results and recent activity. No external services.
 */
class EducationInsights
{
    public const TZ = 'Asia/Karachi';

    /** Record that the student is learning today (and optionally which lesson they opened). */
    public static function touch(CourseEnrollment $enrollment, ?int $lessonId = null): void
    {
        $enrollment->forceFill(array_filter(['last_active_at' => now(), 'last_lesson_id' => $lessonId]))->saveQuietly();
        DB::table('education_activity_days')->insertOrIgnore(['course_enrollment_id' => $enrollment->id, 'day' => now(self::TZ)->toDateString()]);
    }

    /** Insight rows for every active or completed student in a batch, keyed by enrollment id. */
    public static function forBatch(CourseBatch $batch): Collection
    {
        $enrollments = $batch->enrollments()->whereIn('status', ['active', 'completed'])->with('student:id,name,email')->get();

        return self::compute($batch, $enrollments);
    }

    public static function forEnrollment(CourseEnrollment $enrollment): array
    {
        return self::compute($enrollment->batch, collect([$enrollment]))->first();
    }

    private static function compute(CourseBatch $batch, Collection $enrollments): Collection
    {
        if ($enrollments->isEmpty()) {
            return collect();
        }
        $ids = $enrollments->pluck('id')->all();
        $now = CarbonImmutable::now(self::TZ);
        $lessons = $batch->course->lessons()->where('is_published', true)->get(['id', 'title', 'duration_minutes', 'position']);
        $progress = DB::table('course_lesson_progress')->whereIn('course_enrollment_id', $ids)->whereIn('course_lesson_id', $lessons->modelKeys())
            ->get(['course_enrollment_id', 'course_lesson_id', 'completed_at'])->groupBy('course_enrollment_id');
        $completedSessions = EducationSession::where('course_batch_id', $batch->id)->where('status', 'completed')->pluck('id');
        $marks = EducationAttendance::whereIn('course_enrollment_id', $ids)->whereIn('education_session_id', $completedSessions)->get()->groupBy('course_enrollment_id');
        $assignments = EducationAssignment::where('course_batch_id', $batch->id)->where('is_published', true)->get();
        $submissions = EducationSubmission::whereIn('course_enrollment_id', $ids)->whereIn('education_assignment_id', $assignments->modelKeys())->get()->groupBy('course_enrollment_id');
        $quizzes = EducationQuiz::where('course_batch_id', $batch->id)->where('is_published', true)->get();
        $attempts = EducationQuizAttempt::whereIn('course_enrollment_id', $ids)->whereIn('education_quiz_id', $quizzes->modelKeys())->get()->groupBy('course_enrollment_id');
        $days = DB::table('education_activity_days')->whereIn('course_enrollment_id', $ids)->where('day', '>=', $now->subDays(60)->toDateString())
            ->orderByDesc('day')->get()->groupBy('course_enrollment_id');

        // Share of the batch timeline already elapsed: the pace a student "should" be at today.
        $start = CarbonImmutable::parse($batch->starts_on, self::TZ)->startOfDay();
        $end = CarbonImmutable::parse($batch->ends_on, self::TZ)->endOfDay();
        $elapsed = $now->lte($start) ? 0 : ($now->gte($end) ? 1 : $start->diffInSeconds($now) / max(1, $start->diffInSeconds($end)));

        return $enrollments->mapWithKeys(function (CourseEnrollment $e) use ($lessons, $progress, $completedSessions, $marks, $assignments, $submissions, $quizzes, $attempts, $days, $elapsed, $now, $batch) {
            $done = collect($progress[$e->id] ?? [])->pluck('course_lesson_id');
            $total = $lessons->count();
            $expectedLessons = (int) round($elapsed * $total);
            $behind = max(0, $expectedLessons - $done->count());

            $myMarks = collect($marks[$e->id] ?? []);
            $counted = $completedSessions->count() - $myMarks->where('status', 'excused')->count();
            $attendance = $counted > 0 ? (int) floor($myMarks->whereIn('status', ['present', 'late'])->count() * 100 / $counted) : null;
            $requiredAttendance = (int) $batch->required_attendance_percent;

            $mySubs = collect($submissions[$e->id] ?? [])->keyBy('education_assignment_id');
            $missing = $assignments->filter(fn ($a) => $a->due_at && $a->due_at->isPast() && ! in_array($mySubs[$a->id]->status ?? null, ['submitted', 'graded'], true));
            $changes = $mySubs->where('status', 'changes_requested');
            $graded = $mySubs->where('status', 'graded');
            $failedGraded = $graded->filter(fn ($s) => $s->score < ($assignments->firstWhere('id', $s->education_assignment_id)->pass_percent ?? 0));

            $myAttempts = collect($attempts[$e->id] ?? [])->groupBy('education_quiz_id');
            $quizState = $quizzes->map(function ($quiz) use ($myAttempts) {
                $list = collect($myAttempts[$quiz->id] ?? []);

                return ['quiz' => $quiz, 'passed' => $list->contains('passed', true), 'best' => $list->max('score'), 'left' => max(0, $quiz->max_attempts - $list->count()), 'tried' => $list->isNotEmpty()];
            });
            $quizLocked = $quizState->filter(fn ($q) => ! $q['passed'] && $q['left'] === 0 && $q['quiz']->is_required);

            $activity = collect($days[$e->id] ?? [])->pluck('day');
            $lastActive = collect([$e->last_active_at, collect($progress[$e->id] ?? [])->max('completed_at'), $mySubs->max('submitted_at'), collect($attempts[$e->id] ?? [])->max('created_at')])
                ->filter()->map(fn ($t) => CarbonImmutable::parse($t))->max();
            $since = $lastActive ?? ($e->enrolled_at ? CarbonImmutable::parse($e->enrolled_at) : null);
            $inactiveDays = $since ? (int) $since->setTimezone(self::TZ)->startOfDay()->diffInDays($now->startOfDay()) : null;

            // Weighted risk score; reasons are what the instructor and the student see.
            $score = 0;
            $reasons = [];
            if ($total && $behind >= 2) {
                $score += $behind * 100 / $total >= 25 ? 35 : 20;
                $reasons[] = "{$behind} lessons behind the class schedule";
            }
            if ($attendance !== null && ($requiredAttendance ? $attendance < $requiredAttendance : $attendance < 60)) {
                $score += 30;
                $reasons[] = "Attendance is {$attendance}%".($requiredAttendance ? " (needs {$requiredAttendance}%)" : '');
            }
            if ($missing->isNotEmpty()) {
                $score += min(30, 15 * $missing->count());
                $reasons[] = $missing->count() === 1 ? "Missed the deadline for “{$missing->first()->title}”" : "{$missing->count()} assignments past their deadline";
            }
            if ($changes->isNotEmpty()) {
                $score += 10;
                $reasons[] = 'Has work waiting for changes';
            }
            if ($failedGraded->isNotEmpty()) {
                $score += 10;
                $reasons[] = 'Scored below the pass mark on '.($failedGraded->count() === 1 ? 'an assignment' : $failedGraded->count().' assignments');
            }
            if ($quizLocked->isNotEmpty()) {
                $score += 20;
                $reasons[] = "No attempts left on a required quiz (“{$quizLocked->first()['quiz']->title}”)";
            }
            if ($inactiveDays !== null && $inactiveDays >= 7) {
                $score += $inactiveDays >= 14 ? 30 : 20;
                $reasons[] = "No learning activity for {$inactiveDays} days";
            }
            $level = $e->status === 'completed' ? 'completed' : ($score >= 50 ? 'at_risk' : ($score >= 25 ? 'watch' : 'on_track'));

            return [$e->id => [
                'enrollment_id' => $e->id,
                'student' => $e->relationLoaded('student') ? $e->student?->only(['id', 'name', 'email']) : null,
                'status' => $e->status,
                'level' => $level,
                'score' => min(100, $score),
                'reasons' => $reasons,
                'lessons_done' => $done->count(),
                'lessons_total' => $total,
                'progress' => $total ? (int) floor($done->count() * 100 / $total) : null,
                'expected' => (int) floor($elapsed * 100),
                'lessons_behind' => $behind,
                'attendance' => $attendance,
                'required_attendance' => $requiredAttendance,
                'missing' => $missing->count(),
                'pending_review' => $mySubs->where('status', 'submitted')->count(),
                'average_score' => $graded->isNotEmpty() ? (int) round($graded->avg('score')) : null,
                'quizzes_passed' => $quizState->where('passed', true)->count(),
                'quizzes_total' => $quizzes->count(),
                'last_active_at' => $lastActive?->toIso8601String(),
                'inactive_days' => $inactiveDays,
                'streak' => self::streak($activity, $now),
                'remaining_minutes' => (int) $lessons->whereNotIn('id', $done)->sum('duration_minutes'),
                'next_lesson' => ($next = self::nextLesson($lessons, $done, $e->last_lesson_id)) ? $next->only(['id', 'title', 'duration_minutes']) : null,
                '_quizzes' => $quizState,
                '_missing' => $missing,
            ]];
        });
    }

    /** Consecutive days with activity, ending today (or yesterday, so the streak survives until tonight). */
    private static function streak(Collection $days, CarbonImmutable $now): int
    {
        $set = $days->map(fn ($d) => substr((string) $d, 0, 10))->flip();
        $cursor = $now->startOfDay();
        if (! isset($set[$cursor->toDateString()])) {
            $cursor = $cursor->subDay();
        }
        $count = 0;
        while (isset($set[$cursor->toDateString()])) {
            $count++;
            $cursor = $cursor->subDay();
        }

        return $count;
    }

    // Resume after the last opened lesson when it is finished, otherwise the first unfinished one.
    private static function nextLesson(Collection $lessons, Collection $done, ?int $lastLessonId)
    {
        $remaining = $lessons->reject(fn ($l) => $done->contains($l->id))->values();
        if ($remaining->isEmpty()) {
            return null;
        }
        if ($lastLessonId && ($index = $lessons->search(fn ($l) => $l->id === $lastLessonId)) !== false) {
            if (! $done->contains($lastLessonId)) {
                return $lessons[$index];
            }

            return $lessons->slice($index + 1)->first(fn ($l) => ! $done->contains($l->id)) ?? $remaining->first();
        }

        return $remaining->first();
    }

    /** Ordered to-do list for the student's course hub. */
    public static function studentPlan(CourseEnrollment $enrollment): array
    {
        if ($enrollment->status !== 'active') {
            return [];
        }
        $insight = self::forEnrollment($enrollment);
        $now = now();
        $items = [];

        $session = EducationSession::where('course_batch_id', $enrollment->course_batch_id)->where('status', 'scheduled')
            ->where('starts_at', '>=', $now->copy()->subHours(2))->orderBy('starts_at')->first();
        if ($session && $session->starts_at->lte($now->copy()->addDays(7))) {
            $live = $session->starts_at->lte($now->copy()->addMinutes(15));
            $items[] = ['type' => 'class', 'priority' => $live ? 0 : 3, 'title' => $session->title, 'at' => $session->starts_at->toIso8601String(),
                'live' => $live, 'text' => $live ? 'Your class is starting — join now.' : 'Next live class'];
        }
        foreach ($insight['_missing'] as $assignment) {
            if ($assignment->allow_late) {
                $items[] = ['type' => 'assignment', 'priority' => 1, 'id' => $assignment->id, 'title' => $assignment->title, 'at' => $assignment->due_at->toIso8601String(), 'overdue' => true, 'text' => 'Overdue — late submissions are still accepted'];
            }
        }
        $soon = EducationAssignment::where('course_batch_id', $enrollment->course_batch_id)->where('is_published', true)
            ->whereBetween('due_at', [$now, $now->copy()->addDays(3)])
            ->whereDoesntHave('submissions', fn ($q) => $q->where('course_enrollment_id', $enrollment->id)->whereIn('status', ['submitted', 'graded']))->orderBy('due_at')->get();
        foreach ($soon as $assignment) {
            $items[] = ['type' => 'assignment', 'priority' => 2, 'id' => $assignment->id, 'title' => $assignment->title, 'at' => $assignment->due_at->toIso8601String(), 'overdue' => false, 'text' => 'Due soon'];
        }
        $changes = EducationSubmission::where('course_enrollment_id', $enrollment->id)->where('status', 'changes_requested')->with('assignment:id,title')->get();
        foreach ($changes as $submission) {
            $items[] = ['type' => 'assignment', 'priority' => 1, 'id' => $submission->education_assignment_id, 'title' => $submission->assignment->title, 'text' => 'Your instructor asked for changes'];
        }
        if ($insight['next_lesson']) {
            $items[] = ['type' => 'lesson', 'priority' => $insight['lessons_behind'] >= 2 ? 1 : 4, 'id' => $insight['next_lesson']['id'], 'title' => $insight['next_lesson']['title'],
                'minutes' => $insight['next_lesson']['duration_minutes'], 'text' => $insight['lessons_behind'] >= 2 ? "Catch up — you're {$insight['lessons_behind']} lessons behind the class" : 'Continue where you left off'];
        }
        foreach ($insight['_quizzes'] as $quiz) {
            if (! $quiz['passed'] && $quiz['left'] > 0) {
                $items[] = ['type' => 'quiz', 'priority' => $quiz['quiz']->is_required ? 3 : 5, 'id' => $quiz['quiz']->id, 'title' => $quiz['quiz']->title,
                    'text' => $quiz['tried'] ? "Best score {$quiz['best']}% · {$quiz['left']} ".($quiz['left'] === 1 ? 'attempt' : 'attempts').' left' : ($quiz['quiz']->is_required ? 'Required quiz' : 'Practice quiz')];
            }
        }
        usort($items, fn ($a, $b) => [$a['priority'], $a['at'] ?? ''] <=> [$b['priority'], $b['at'] ?? '']);

        return ['insight' => self::public($insight), 'todo' => array_slice($items, 0, 6)];
    }

    /** Strip internal working fields before sending to the browser. */
    public static function public(array $row): array
    {
        return array_diff_key($row, ['_quizzes' => 1, '_missing' => 1]);
    }
}
