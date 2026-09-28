<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseBatch;
use App\Models\CourseEnrollment;
use App\Support\Education;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:200', 'mode' => ['nullable', Rule::in(['online', 'in_person', 'hybrid'])], 'level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced'])]]);
        $courses = Course::where('is_published', true)
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('category', 'like', "%{$search}%")))
            ->when($filters['mode'] ?? null, fn ($q, $mode) => $q->where('mode', $mode))
            ->when($filters['level'] ?? null, fn ($q, $level) => $q->where('level', $level))
            ->withCount(['lessons' => fn ($q) => $q->where('is_published', true)])
            ->latest()->paginate(12)->withQueryString();

        return ReactPage::render('education.catalog', compact('courses', 'filters'));
    }

    public function show(string $slug)
    {
        $course = Course::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $batches = $course->batches()->where('is_open', true)->whereDate('applications_close_on', '>=', today())->whereDate('ends_on', '>=', today())
            ->withCount(['enrollments as reserved_count' => fn ($q) => $q->whereIn('status', Education::RESERVED)])
            ->orderBy('starts_on')->get()->makeHidden('meeting_url');
        $lessons = $course->lessons()->where('is_published', true)->get(['id', 'course_id', 'title', 'duration_minutes', 'position']);
        $applications = auth()->check() ? CourseEnrollment::where('user_id', auth()->id())->whereHas('batch', fn ($q) => $q->where('course_id', $course->id))->get(['id', 'course_batch_id', 'status']) : [];

        return ReactPage::render('education.course', compact('course', 'batches', 'lessons', 'applications'));
    }

    public function apply(Request $request, CourseBatch $batch)
    {
        abort_if($request->user()->is_admin, 403, 'Use a student account to apply.');
        $data = $request->validate(['phone' => 'required|string|max:40', 'motivation' => 'required|string|max:5000']);
        $enrollment = DB::transaction(function () use ($request, $batch, $data) {
            $batch = CourseBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($existing = $batch->enrollments()->where('user_id', $request->user()->id)->first()) {
                return $existing;
            }
            abort_unless($batch->course->is_published, 404);
            Education::ensure($batch->is_open && $batch->applications_close_on->gte(today()) && $batch->ends_on->gte(today()), 'Applications for this batch are closed.');
            Education::ensure($batch->enrollments()->whereIn('status', Education::RESERVED)->count() < $batch->capacity, 'This batch is full. Please choose another batch.');

            return $batch->enrollments()->create($data + ['user_id' => $request->user()->id, 'fee_minor' => $batch->course->fee_minor, 'currency' => $batch->course->currency]);
        });

        return redirect()->route('education.enrollments.show', $enrollment)->with('success', 'Your application has been received. Track its progress here.');
    }
}
