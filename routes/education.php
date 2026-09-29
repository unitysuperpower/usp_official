<?php

use App\Http\Controllers\Education\AdminCourseController;
use App\Http\Controllers\Education\AdminEnrollmentController;
use App\Http\Controllers\Education\CourseController;
use App\Http\Controllers\Education\EnrollmentController;
use App\Http\Controllers\Education\JazzCashController;
use Illuminate\Support\Facades\Route;

Route::get('courses', [CourseController::class, 'index'])->name('education.courses.index');
Route::get('courses/{slug}', [CourseController::class, 'show'])->name('education.courses.show');
Route::middleware('auth')->name('education.')->group(function () {
    Route::post('education/batches/{batch}/apply', [CourseController::class, 'apply'])->middleware('throttle:10,1')->name('apply');
    Route::get('learn', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('learn/{enrollment}', [EnrollmentController::class, 'show'])->name('enrollments.show');
    Route::post('learn/{enrollment}/cancel', [EnrollmentController::class, 'cancel'])->name('cancel');
    Route::post('learn/{enrollment}/payments', [EnrollmentController::class, 'payment'])->middleware('throttle:10,1')->name('payments.store');
    Route::post('education/payments/{payment}/resubmit', [EnrollmentController::class, 'resubmit'])->middleware('throttle:10,1')->name('payments.resubmit');
    Route::get('education/payments/{payment}/proof', [EnrollmentController::class, 'proof'])->name('payments.proof');
    Route::post('learn/{enrollment}/lessons/{lesson}/complete', [EnrollmentController::class, 'progress'])->name('progress');
    Route::get('learn/{enrollment}/certificate', [EnrollmentController::class, 'certificate'])->name('certificate');
});
Route::middleware(['auth', 'admin'])->prefix('admin/education')->name('admin.education.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.education.enrollments.index'));
    Route::get('courses', [AdminCourseController::class, 'index'])->name('courses.index');
    Route::get('courses/create', [AdminCourseController::class, 'create'])->name('courses.create');
    Route::post('courses', [AdminCourseController::class, 'store'])->name('courses.store');
    Route::get('courses/{course}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
    Route::put('courses/{course}', [AdminCourseController::class, 'update'])->name('courses.update');
    Route::post('courses/{course}/batches', [AdminCourseController::class, 'batch'])->name('batches.store');
    Route::put('courses/{course}/batches/{batch}', [AdminCourseController::class, 'batch'])->name('batches.update');
    Route::post('courses/{course}/lessons', [AdminCourseController::class, 'lesson'])->name('lessons.store');
    Route::put('courses/{course}/lessons/{lesson}', [AdminCourseController::class, 'lesson'])->name('lessons.update');
    Route::get('enrollments', [AdminEnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('enrollments/{enrollment}', [AdminEnrollmentController::class, 'show'])->name('enrollments.show');
    Route::patch('enrollments/{enrollment}', [AdminEnrollmentController::class, 'review'])->name('enrollments.review');
    Route::get('payments', [AdminEnrollmentController::class, 'payments'])->name('payments.index');
    Route::patch('payments/{payment}', [AdminEnrollmentController::class, 'verify'])->name('payments.verify');
});

Route::post('learn/{enrollment}/checkout', [JazzCashController::class, 'checkout'])->middleware(['auth', 'throttle:10,1'])->name('education.checkout');
Route::post('education/jazzcash/return', [JazzCashController::class, 'callback'])->middleware('throttle:120,1')->name('education.jazzcash.return');

Route::middleware(['auth', 'admin'])->prefix('admin/education')->name('admin.education.')->group(function () {
    Route::get('settings', [\App\Http\Controllers\Education\PaymentSettingsController::class, 'edit'])->name('settings');
    Route::put('settings', [\App\Http\Controllers\Education\PaymentSettingsController::class, 'update'])->middleware('throttle:10,1')->name('settings.update');
    Route::get('batches/{batch}/classroom', [\App\Http\Controllers\Education\AdminLearningController::class, 'show'])->name('classroom');
    Route::patch('batches/{batch}/requirements', [\App\Http\Controllers\Education\AdminLearningController::class, 'requirements'])->name('requirements');
    Route::post('batches/{batch}/sessions', [\App\Http\Controllers\Education\AdminLearningController::class, 'session'])->name('sessions.store');
    Route::put('batches/{batch}/sessions/{session}', [\App\Http\Controllers\Education\AdminLearningController::class, 'session'])->name('sessions.update');
    Route::post('batches/{batch}/sessions/{session}/attendance', [\App\Http\Controllers\Education\AdminLearningController::class, 'attendance'])->name('attendance');
    Route::post('batches/{batch}/assignments', [\App\Http\Controllers\Education\AdminLearningController::class, 'assignment'])->name('assignments.store');
    Route::put('batches/{batch}/assignments/{assignment}', [\App\Http\Controllers\Education\AdminLearningController::class, 'assignment'])->name('assignments.update');
    Route::patch('submissions/{submission}', [\App\Http\Controllers\Education\AdminLearningController::class, 'grade'])->name('submissions.grade');
    Route::post('batches/{batch}/quizzes', [\App\Http\Controllers\Education\AdminLearningController::class, 'quiz'])->name('quizzes.store');
    Route::put('batches/{batch}/quizzes/{quiz}', [\App\Http\Controllers\Education\AdminLearningController::class, 'quiz'])->name('quizzes.update');
});
Route::middleware('auth')->name('education.')->group(function () {
    Route::post('learn/{enrollment}/assignments/{assignment}', [\App\Http\Controllers\Education\LearningController::class, 'submit'])->middleware('throttle:20,1')->name('assignments.submit');
    Route::post('learn/{enrollment}/quizzes/{quiz}', [\App\Http\Controllers\Education\LearningController::class, 'quiz'])->middleware('throttle:10,1')->name('quizzes.submit');
    Route::get('education/submissions/{submission}/file', [\App\Http\Controllers\Education\LearningController::class, 'file'])->name('submissions.file');
});
