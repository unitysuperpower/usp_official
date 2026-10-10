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
        $filters = $request->validate(['category' => 'nullable|string|max:255', 'sort' => 'nullable|in:newest,price_low,price_high', 'search' => 'nullable|string|max:200', 'mode' => ['nullable', Rule::in(['online', 'in_person', 'hybrid'])], 'level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced'])]]);
        $courses = Course::where('is_published', true)
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('category', 'like', "%{$search}%")))
            ->when($filters['mode'] ?? null, fn ($q, $mode) => $q->where('mode', $mode))
            ->when($filters['level'] ?? null, fn ($q, $level) => $q->where('level', $level))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->withMin(['batches as next_batch_on' => fn ($q) => $q->where('is_open', true)->whereDate('applications_close_on', '>=', today())->whereDate('starts_on', '>=', today())], 'starts_on')
            ->when(($filters['sort'] ?? '') === 'price_low', fn ($q) => $q->orderBy('fee_minor'))->when(($filters['sort'] ?? '') === 'price_high', fn ($q) => $q->orderByDesc('fee_minor'))
            ->withCount(['lessons' => fn ($q) => $q->where('is_published', true)])
            ->latest()->paginate(12)->withQueryString();

        abort_if($courses->currentPage() > $courses->lastPage(), 404);

        $featured = Course::where('is_published', true)->where('is_featured', true)->orderBy('carousel_position')->orderBy('id')->limit(12)->get();

        $courseCategories = Course::where('is_published', true)->select('category')->distinct()->orderBy('category')->pluck('category');

        return ReactPage::render('education.catalog', compact('courses', 'filters', 'featured', 'courseCategories'));
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

    public function enroll(Request $request, CourseBatch $batch)
    {
        abort_if($request->user()->is_admin, 403, 'Use a student account to enroll.');
        if ($existing = $batch->enrollments()->where('user_id', $request->user()->id)->first()) {
            return redirect()->route('education.enrollments.show', $existing);
        }
        $batch->load('course');
        abort_unless($batch->course->is_published, 404);
        $seats = max(0, $batch->capacity - $batch->enrollments()->whereIn('status', Education::RESERVED)->count());
        $available = $batch->is_open && $batch->applications_close_on->gte(today()) && $batch->ends_on->gte(today()) && $seats > 0;
        $batch->makeHidden('meeting_url');

        return ReactPage::render('education.enroll', compact('batch', 'seats', 'available'));
    }

    public function apply(Request $request, CourseBatch $batch)
    {
        abort_if($request->user()->is_admin, 403, 'Use a student account to apply.');
        $data = $request->validate(['phone' => 'required|string|max:40', 'motivation' => 'required|string|max:5000', 'quoted_fee_minor' => 'sometimes|required|integer|min:0', 'quoted_currency' => 'sometimes|required|string|size:3']);
        $enrollment = DB::transaction(function () use ($request, $batch, $data) {
            $batch = CourseBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($existing = $batch->enrollments()->where('user_id', $request->user()->id)->first()) {
                return $existing;
            }
            abort_unless($batch->course->is_published, 404);
            Education::ensure($batch->is_open && $batch->applications_close_on->gte(today()) && $batch->ends_on->gte(today()), 'Applications for this batch are closed.');
            Education::ensure($batch->enrollments()->whereIn('status', Education::RESERVED)->count() < $batch->capacity, 'This batch is full. Please choose another batch.');

            Education::ensure(! isset($data['quoted_fee_minor']) || (int) $data['quoted_fee_minor'] === $batch->course->fee_minor, 'The course fee changed. Refresh this page and review the updated fee before applying.', 'quoted_fee_minor');
            Education::ensure(! isset($data['quoted_currency']) || $data['quoted_currency'] === $batch->course->currency, 'The course currency changed. Refresh this page before applying.', 'quoted_currency');
            unset($data['quoted_fee_minor'],$data['quoted_currency']);

            return $batch->enrollments()->create($data + ['user_id' => $request->user()->id, 'fee_minor' => $batch->course->fee_minor, 'currency' => $batch->course->currency]);
        });

        return redirect()->route('education.enrollments.show', $enrollment)->with('success', 'Your application has been received. Track its progress here.');
    }
}
