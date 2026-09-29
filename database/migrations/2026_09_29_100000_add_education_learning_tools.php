<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_batches', function (Blueprint $table) {
            $table->unsignedTinyInteger('required_attendance_percent')->default(0);
        });
        Schema::create('education_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_batch_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('starts_at');
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('education_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('note', 500)->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['education_session_id', 'course_enrollment_id'], 'education_attendance_unique');
        });
        Schema::create('education_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_batch_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions');
            $table->dateTime('due_at')->nullable();
            $table->boolean('allow_late')->default(true);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_required')->default(false);
            $table->unsignedTinyInteger('pass_percent')->default(60);
            $table->timestamps();
        });
        Schema::create('education_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_enrollment_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('status')->default('submitted');
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->dateTime('submitted_at');
            $table->dateTime('graded_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['education_assignment_id', 'course_enrollment_id'], 'education_submission_unique');
        });
        Schema::create('education_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_batch_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->json('questions');
            $table->unsignedTinyInteger('pass_percent')->default(60);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });
        Schema::create('education_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_enrollment_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->unsignedTinyInteger('score');
            $table->boolean('passed');
            $table->unsignedTinyInteger('attempt_number');
            $table->timestamps();
            $table->unique(['education_quiz_id', 'course_enrollment_id', 'attempt_number'], 'education_quiz_attempt_unique');
        });
        Schema::create('education_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->json('manual_methods')->nullable();
            $table->text('gateway')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['education_settings', 'education_quiz_attempts', 'education_quizzes', 'education_submissions', 'education_assignments', 'education_attendance', 'education_sessions'] as $table) Schema::dropIfExists($table);
        Schema::table('course_batches', fn (Blueprint $table) => $table->dropColumn('required_attendance_percent'));
    }
};
