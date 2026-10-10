<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\EducationAssignment;
use App\Models\EducationSession;
use App\Support\ReactPage;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    private function events(Request $request): array
    {
        $active = CourseEnrollment::where('user_id', $request->user()->id)->where('status', 'active')->pluck('id', 'course_batch_id');
        $start = now();
        $end = $start->copy()->addDays(90);
        $sessions = EducationSession::whereIn('course_batch_id', $active->keys())->where('status', 'scheduled')->whereBetween('starts_at', [$start, $end])->with('batch.course:id,title')->orderBy('starts_at')->limit(300)->get()->map(fn ($s) => ['id' => 'session-'.$s->id, 'kind' => 'Class', 'title' => $s->title, 'course' => $s->batch->course->title, 'at' => $s->starts_at->toIso8601String(), 'enrollment_id' => $active[$s->course_batch_id]]);
        $assignments = EducationAssignment::whereIn('course_batch_id', $active->keys())->where('is_published', true)->whereBetween('due_at', [$start, $end])->whereDoesntHave('submissions', fn ($q) => $q->whereIn('course_enrollment_id', $active->values())->whereIn('status', ['submitted', 'graded']))->with('batch.course:id,title')->orderBy('due_at')->limit(300)->get()->map(fn ($a) => ['id' => 'assignment-'.$a->id, 'kind' => 'Assignment due', 'title' => $a->title, 'course' => $a->batch->course->title, 'at' => $a->due_at->toIso8601String(), 'enrollment_id' => $active[$a->course_batch_id]]);

        return $sessions->concat($assignments)->sortBy('at')->values()->all();
    }

    public function index(Request $request)
    {
        return response(ReactPage::render('education.calendar', ['events' => $this->events($request)]))->header('Cache-Control', 'private, no-store');
    }

    public function download(Request $request)
    {
        $escape = fn ($text) => str_replace(['\\', "\r\n", "\r", "\n", ';', ','], ['\\\\', '\\n', '\\n', '\\n', '\\;', '\\,'], $text);
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//USP Education//Learning Calendar//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH'];
        foreach ($this->events($request) as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.$event['id'].'-enrollment-'.$event['enrollment_id'].'@usp-education';
            $lines[] = 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART:'.Carbon::parse($event['at'])->utc()->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:'.$escape($event['kind'].': '.$event['title']);
            $lines[] = 'DESCRIPTION:'.$escape($event['course']."\nOpen your USP learning workspace for current details. This entry marks the start time or deadline only.");
            $lines[] = 'URL:'.url('/learn/'.$event['enrollment_id']);
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';
        $folded = [];
        foreach ($lines as $line) {
            $limit = 75;
            while (strlen($line) > $limit) {
                $chunk = mb_strcut($line, 0, $limit, 'UTF-8');
                $folded[] = $chunk;
                $line = ' '.substr($line, strlen($chunk));
            }
            $folded[] = $line;
        }

        return response(implode("\r\n", $folded)."\r\n")->header('Content-Type', 'text/calendar; charset=utf-8')->header('Content-Disposition', 'attachment; filename="usp-learning-calendar.ics"')->header('Cache-Control','private, no-store')->header('X-Content-Type-Options','nosniff');
    }
}
