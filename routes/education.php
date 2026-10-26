<?php

use App\Http\Controllers\Education\AdminCourseController;
use App\Http\Controllers\Education\AdminEnrollmentController;
use App\Http\Controllers\Education\AdminLearningController;
use App\Http\Controllers\Education\CalendarController;
use App\Http\Controllers\Education\CourseController;
use App\Http\Controllers\Education\EnrollmentController;
use App\Http\Controllers\Education\ExperienceController;
use App\Http\Controllers\Education\JazzCashController;
use App\Http\Controllers\Education\LearningController;
use App\Http\Controllers\Education\PaymentSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('courses', [CourseController::class, 'index'])->name('education.courses.index');
Route::get('courses/{slug}', [CourseController::class, 'show'])->name('education.courses.show');
Route::middleware('auth')->name('education.')->group(function () {
    Route::get('education/batches/{batch}/enroll', [CourseController::class, 'enroll'])->name('enroll');
    Route::post('education/batches/{batch}/apply', [CourseController::class, 'apply'])->middleware('throttle:10,1')->name('apply');
    Route::get('learn', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('learn/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('learn/calendar/download', [CalendarController::class, 'download'])->name('calendar.download');
    Route::get('learn/{enrollment}', [EnrollmentController::class, 'show'])->name('enrollments.show');
    Route::post('learn/{enrollment}/cancel', [EnrollmentController::class, 'cancel'])->name('cancel');
    Route::post('learn/{enrollment}/payments', [EnrollmentController::class, 'payment'])->middleware('throttle:10,1')->name('payments.store');
    Route::post('education/payments/{payment}/resubmit', [EnrollmentController::class, 'resubmit'])->middleware('throttle:10,1')->name('payments.resubmit');
    Route::get('education/payments/{payment}/proof', [EnrollmentController::class, 'proof'])->name('payments.proof');
    Route::post('learn/{enrollment}/lessons/{lesson}/complete', [EnrollmentController::class, 'progress'])->name('progress');
    Route::post('learn/{enrollment}/lessons/{lesson}/open', [EnrollmentController::class, 'open'])->middleware('throttle:120,1')->name('lessons.open');
    Route::get('learn/{enrollment}/certificate', [EnrollmentController::class, 'certificate'])->name('certificate');
});
Route::middleware(['auth', 'admin'])->prefix('admin/education')->name('admin.education.')->group(function () {
    Route::get('/', fn () => redirect(request()->user()->can('education.enrollments') ? route('admin.education.enrollments.index') : url('admin/education/overview')))->middleware('can:education.any');
    Route::middleware('can:education.courses')->group(function () {
        Route::get('courses', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/create', [AdminCourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [AdminCourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
        Route::put('courses/{course}', [AdminCourseController::class, 'update'])->name('courses.update');
        Route::post('courses/{course}/batches', [AdminCourseController::class, 'batch'])->name('batches.store');
        Route::put('courses/{course}/batches/{batch}', [AdminCourseController::class, 'batch'])->name('batches.update');
        Route::post('courses/{course}/lessons', [AdminCourseController::class, 'lesson'])->name('lessons.store');
        Route::put('courses/{course}/lessons/{lesson}', [AdminCourseController::class, 'lesson'])->name('lessons.update');
        Route::delete('courses/{course}/lessons/{lesson}', [AdminCourseController::class, 'destroyLesson'])->name('lessons.destroy');
        Route::patch('courses/{course}/lessons/{lesson}/move', [AdminCourseController::class, 'moveLesson'])->name('lessons.move');
        Route::delete('courses/{course}/batches/{batch}', [AdminCourseController::class, 'destroyBatch'])->name('batches.destroy');
    });
    Route::middleware('can:education.enrollments')->group(function () {
        Route::get('enrollments', [AdminEnrollmentController::class, 'index'])->name('enrollments.index');
        Route::get('enrollments/{enrollment}', [AdminEnrollmentController::class, 'show'])->name('enrollments.show');
        Route::patch('enrollments/{enrollment}', [AdminEnrollmentController::class, 'review'])->name('enrollments.review');
    });
    Route::middleware('can:education.payments')->group(function () {
        Route::get('payments', [AdminEnrollmentController::class, 'payments'])->name('payments.index');
        Route::patch('payments/{payment}', [AdminEnrollmentController::class, 'verify'])->name('payments.verify');
    });
});

Route::post('learn/{enrollment}/checkout', [JazzCashController::class, 'checkout'])->middleware(['auth', 'throttle:10,1'])->name('education.checkout');
Route::post('education/jazzcash/return', [JazzCashController::class, 'callback'])->middleware('throttle:120,1')->name('education.jazzcash.return');

Route::middleware(['auth', 'admin', 'can:education.settings'])->prefix('admin/education')->name('admin.education.')->group(function () {
    Route::get('settings', [PaymentSettingsController::class, 'edit'])->name('settings');
    Route::put('settings', [PaymentSettingsController::class, 'update'])->middleware('throttle:10,1')->name('settings.update');
});
Route::get('admin/education/teaching', [AdminLearningController::class, 'teaching'])->middleware(['auth', 'admin', 'can:education.teach'])->name('admin.education.teaching');
Route::middleware(['auth', 'admin', 'can:education.teach', 'teaches'])->prefix('admin/education')->name('admin.education.')->group(function () {
    Route::get('batches/{batch}/classroom', [AdminLearningController::class, 'show'])->name('classroom');
    Route::patch('batches/{batch}/requirements', [AdminLearningController::class, 'requirements'])->name('requirements');
    Route::post('batches/{batch}/sessions', [AdminLearningController::class, 'session'])->name('sessions.store');
    Route::put('batches/{batch}/sessions/{session}', [AdminLearningController::class, 'session'])->name('sessions.update');
    Route::post('batches/{batch}/sessions/{session}/attendance', [AdminLearningController::class, 'attendance'])->name('attendance');
    Route::post('batches/{batch}/assignments', [AdminLearningController::class, 'assignment'])->name('assignments.store');
    Route::put('batches/{batch}/assignments/{assignment}', [AdminLearningController::class, 'assignment'])->name('assignments.update');
    Route::patch('submissions/{submission}', [AdminLearningController::class, 'grade'])->name('submissions.grade');
    Route::post('batches/{batch}/quizzes', [AdminLearningController::class, 'quiz'])->name('quizzes.store');
    Route::put('batches/{batch}/quizzes/{quiz}', [AdminLearningController::class, 'quiz'])->name('quizzes.update');
    Route::post('batches/{batch}/students/{enrollment}/nudge', [AdminLearningController::class, 'nudge'])->middleware('throttle:30,1')->name('nudge');
    Route::get('batches/{batch}/gradebook.csv', [AdminLearningController::class, 'gradebook'])->name('gradebook');
});
Route::middleware('auth')->name('education.')->group(function () {
    Route::post('learn/{enrollment}/assignments/{assignment}', [LearningController::class, 'submit'])->middleware('throttle:20,1')->name('assignments.submit');
    Route::post('learn/{enrollment}/quizzes/{quiz}', [LearningController::class, 'quiz'])->middleware('throttle:10,1')->name('quizzes.submit');
    Route::get('education/submissions/{submission}/file', [LearningController::class, 'file'])->name('submissions.file');
});

Route::middleware('auth')->group(function () {
    Route::put('learn/{enrollment}/lessons/{lesson}/note', [ExperienceController::class, 'note'])->middleware('throttle:30,1');
    Route::get('education/payments/{payment}/receipt', [ExperienceController::class, 'receipt']);
});
Route::get('admin/education/overview', [ExperienceController::class, 'overview'])->middleware(['auth', 'admin', 'can:education.any']);
Route::middleware(['auth', 'admin', 'can:education.teach', 'teaches'])->prefix('admin/education')->group(function () {
    Route::post('batches/{batch}/announcements', [ExperienceController::class, 'announce']);
    Route::delete('batches/{batch}/announcements/{announcement}', [ExperienceController::class, 'removeAnnouncement']);
});
