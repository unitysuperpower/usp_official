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
    }
}
