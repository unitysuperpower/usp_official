<?php

namespace App\Console\Commands;

use App\Models\CourseEnrollment;
use App\Models\EducationAssignment;
use App\Models\EducationSession;
use App\Support\EducationInsights;
use App\Support\SiteNotifications;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Scheduled hourly. Each reminder has a unique key in education_reminders, so it is sent once.
class SendEducationReminders extends Command
{
    protected $signature = 'education:reminders {--dry-run : List what would be sent without emailing}';

    protected $description = 'Email students about classes in the next 24 hours, assignments due soon, and a week without learning';

    private int $sent = 0;

    public function handle(): int
    {
        $tz = EducationInsights::TZ;
        $active = fn () => CourseEnrollment::where('status', 'active');

        foreach (EducationSession::where('status', 'scheduled')->whereBetween('starts_at', [now(), now()->addDay()])->get() as $session) {
            foreach ($active()->where('course_batch_id', $session->course_batch_id)->get() as $enrollment) {
                $this->remind("class:{$session->id}:{$enrollment->id}", $enrollment, fn () => SiteNotifications::educationReminder($enrollment, 'Class reminder',
                    'You have a live class coming up. Join on time so you don’t miss anything.',
                    ['Class' => $session->title, 'Starts' => $session->starts_at->timezone($tz)->format('l, j F · g:i a').' (PKT)'], '?tab=classes'));
            }
        }

        $assignments = EducationAssignment::where('is_published', true)->whereBetween('due_at', [now(), now()->addDay()])->get();
        foreach ($assignments as $assignment) {
            $enrollments = $active()->where('course_batch_id', $assignment->course_batch_id)
                ->whereDoesntHave('submissions', fn ($q) => $q->where('education_assignment_id', $assignment->id)->whereIn('status', ['submitted', 'graded']))->get();
            foreach ($enrollments as $enrollment) {
                $this->remind("due:{$assignment->id}:{$enrollment->id}", $enrollment, fn () => SiteNotifications::educationReminder($enrollment, 'Assignment due soon',
                    'An assignment is due within 24 hours and we haven’t received your work yet.',
                    ['Assignment' => $assignment->title, 'Due' => $assignment->due_at->timezone($tz)->format('l, j F · g:i a').' (PKT)'], '?tab=assignments'));
            }
        }

        // Inactivity: once per week, only while the batch is running.
        $week = now($tz)->format('o-\WW');
        $quiet = $active()->whereHas('batch', fn ($q) => $q->where('starts_on', '<=', now($tz)->toDateString())->where('ends_on', '>=', now($tz)->toDateString()))
            ->where(fn ($q) => $q->where('last_active_at', '<', now()->subDays(7))->orWhere(fn ($w) => $w->whereNull('last_active_at')->where('enrolled_at', '<', now()->subDays(7))))->get();
        foreach ($quiet as $enrollment) {
            $insight = EducationInsights::forEnrollment($enrollment);
            $this->remind("inactive:{$enrollment->id}:{$week}", $enrollment, fn () => SiteNotifications::educationReminder($enrollment, 'We miss you in class',
                'It’s been a while since your last lesson. A few minutes today keeps you on track.',
                array_filter(['Your progress' => $insight['progress'] !== null ? $insight['progress'].'% complete' : null, 'Up next' => $insight['next_lesson']['title'] ?? null]), '?tab=lessons'));
        }

        $this->info(($this->option('dry-run') ? 'Would send ' : 'Sent ').$this->sent.' reminder(s).');

        return self::SUCCESS;
    }

    private function remind(string $key, CourseEnrollment $enrollment, callable $send): void
    {
        if ($this->option('dry-run')) {
            if (! DB::table('education_reminders')->where('key', $key)->exists()) {
                $this->line($key);
                $this->sent++;
            }

            return;
        }
        if (DB::table('education_reminders')->insertOrIgnore(['key' => $key, 'course_enrollment_id' => $enrollment->id, 'sent_at' => now()]) === 1) {
            $send();
            $this->sent++;
        }
    }
}
