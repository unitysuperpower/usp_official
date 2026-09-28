<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseBatch;
use App\Models\CourseLesson;
use App\Support\Education;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminCourseController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:200']);
        $courses = Course::withCount(['batches', 'lessons'])->when($filters['search'] ?? null, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))->latest()->paginate(20)->withQueryString();

        return ReactPage::render('admin.education.courses', compact('courses', 'filters'));
    }

    public function create()
    {
        return ReactPage::render('admin.education.editor', ['currency' => config('education.currency')]);
    }

    public function edit(Course $course)
    {
        $course->load(['batches' => fn ($q) => $q->withCount(['enrollments as reserved_count' => fn ($q) => $q->whereIn('status', Education::RESERVED)])->orderByDesc('starts_on'), 'lessons']);

        return ReactPage::render('admin.education.editor', compact('course'));
    }

    private function data(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255', 'slug' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('courses')->ignore($course?->id)],
            'category' => 'required|string|max:255', 'level' => 'required|in:beginner,intermediate,advanced',
            'mode' => 'required|in:online,in_person,hybrid', 'summary' => 'required|string|max:500',
            'description' => 'required|string|max:30000', 'outcomes' => 'nullable|string|max:10000',
            'prerequisites' => 'nullable|string|max:5000', 'instructor' => 'required|string|max:255',
            'duration_hours' => 'required|integer|min:1|max:10000', 'fee' => 'required|numeric|min:0|max:10000000|decimal:0,2',
            'is_published' => 'required|boolean',
        ]);
        $data['fee_minor'] = (int) round((float) $data['fee'] * 100);
        $data['currency'] = $course?->currency ?? config('education.currency');
        $data['is_published'] = $request->boolean('is_published');
        unset($data['fee']);

        return $data;
    }

    public function store(Request $request)
    {
        $course = Course::create($this->data($request));

        return redirect()->route('admin.education.courses.edit', $course)->with('success', 'Course created. Add batches and lessons below.');
    }

    public function update(Request $request, Course $course)
    {
        $course->update($this->data($request, $course));

        return back()->with('success', 'Course saved. Existing applications keep their original fee.');
    }

    public function batch(Request $request, Course $course, ?CourseBatch $batch = null)
    {
        abort_if($batch && $batch->course_id !== $course->id, 404);
        $data = $request->validate([
            'name' => 'required|string|max:255', 'starts_on' => 'required|date_format:Y-m-d',
            'ends_on' => 'required|date_format:Y-m-d|after_or_equal:starts_on',
            'applications_close_on' => 'required|date_format:Y-m-d|before_or_equal:starts_on',
            'capacity' => 'required|integer|min:1|max:100000', 'schedule' => 'required|string|max:255',
            'location' => 'nullable|required_if:mode,in_person,hybrid|string|max:255',
            'meeting_url' => 'nullable|url:https|max:2000', 'is_open' => 'required|boolean',
        ]);
        if (in_array($course->mode, ['in_person', 'hybrid'])) {
            Education::ensure(filled($data['location'] ?? null), 'Enter the class venue for in-person or hybrid delivery.', 'location');
        }
        $data['is_open'] = $request->boolean('is_open');
        DB::transaction(function () use ($course, $batch, $data) {
            if ($batch) {
                $batch = CourseBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
                Education::ensure($data['capacity'] >= $batch->enrollments()->whereIn('status', Education::RESERVED)->count(), 'Capacity cannot be lower than reserved seats.', 'capacity');
                $batch->update($data);
            } else {
                $course->batches()->create($data);
            }
        });

        return back()->with('success', 'Batch saved.');
    }

    public function lesson(Request $request, Course $course, ?CourseLesson $lesson = null)
    {
        abort_if($lesson && $lesson->course_id !== $course->id, 404);
        $data = $request->validate([
            'title' => 'required|string|max:255', 'position' => 'required|integer|min:1|max:10000',
            'duration_minutes' => 'required|integer|min:0|max:10000', 'content' => 'required|string|max:100000',
            'video_url' => 'nullable|url:https|max:2000', 'resource_url' => 'nullable|url:https|max:2000',
            'is_published' => 'required|boolean',
        ]);
        $data['is_published'] = $request->boolean('is_published');
        if ($lesson) {
            $lesson->update($data);
        } else {
            $course->lessons()->create($data);
        }

        return back()->with('success', 'Lesson saved.');
    }
}
