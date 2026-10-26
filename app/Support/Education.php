<?php

namespace App\Support;

use App\Models\CourseBatch;
use App\Models\CourseEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Education
{
    public const RESERVED = ['accepted', 'active', 'completed'];

    public static function methods(): array
    {
        return array_filter(\App\Models\EducationSetting::current()?->manual_methods ?? config('education.payment_methods'), fn ($method) => filled($method['instructions']));
    }

    /** Course managers see every classroom; instructors only the courses they are assigned to. */
    public static function teaches(\App\Models\User $user, CourseBatch $batch): bool
    {
        return $user->can('education.courses')
            || ($user->can('education.teach') && DB::table('course_instructors')->where('course_id', $batch->course_id)->where('user_id', $user->id)->exists());
    }

    /** Batch ids the user may teach, or null for all of them. */
    public static function teachingBatchIds(\App\Models\User $user): ?\Illuminate\Support\Collection
    {
        if ($user->can('education.courses')) {
            return null;
        }

        return CourseBatch::whereIn('course_id', DB::table('course_instructors')->where('user_id', $user->id)->select('course_id'))->pluck('id');
    }

    public static function ensure(bool $condition, string $message, string $field = 'status'): void
    {
        if (! $condition) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    // Consistent lock order prevents overbooking and payment/review races.
    public static function locked(CourseEnrollment $enrollment, callable $callback): mixed
    {
        return DB::transaction(function () use ($enrollment, $callback) {
            $batch = CourseBatch::whereKey($enrollment->course_batch_id)->lockForUpdate()->firstOrFail();
            $fresh = CourseEnrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();

            return $callback($fresh, $batch);
        });
    }

    public static function paid(CourseEnrollment $enrollment): void
    {
        self::ensure($enrollment->status === 'accepted', 'This enrollment is no longer awaiting payment.');
        $enrollment->update(['status' => 'active', 'enrolled_at' => now()]);
        SiteNotifications::enrollmentConfirmed($enrollment);
    }
}
