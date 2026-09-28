<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category');
            $table->string('level')->default('beginner');
            $table->string('mode')->default('online');
            $table->string('summary', 500);
            $table->text('description');
            $table->text('outcomes')->nullable();
            $table->text('prerequisites')->nullable();
            $table->string('instructor');
            $table->unsignedInteger('duration_hours');
            $table->unsignedBigInteger('fee_minor')->default(0);
            $table->string('currency', 3)->default('PKR');
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });
        Schema::create('course_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->date('applications_close_on');
            $table->unsignedInteger('capacity');
            $table->string('schedule');
            $table->string('location')->nullable();
            $table->text('meeting_url')->nullable();
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('position')->default(1);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->longText('content');
            $table->text('video_url')->nullable();
            $table->text('resource_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_batch_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('applied')->index();
            $table->string('phone', 40);
            $table->text('motivation');
            $table->text('review_note')->nullable();
            $table->unsignedBigInteger('fee_minor');
            $table->string('currency', 3);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('certificate_code')->nullable()->unique();
            $table->string('certificate_name')->nullable();
            $table->string('certificate_course')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_batch_id']);
        });
        Schema::create('course_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_enrollment_id')->constrained()->restrictOnDelete();
            $table->string('method', 30);
            $table->string('reference', 100);
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('proof_path');
            $table->string('status')->default('pending')->index();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['method', 'reference']);
        });
        Schema::create('course_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->unique(['course_enrollment_id', 'course_lesson_id']);
        });
    }

    public function down(): void
    {
        foreach (['course_lesson_progress', 'course_payments', 'course_enrollments', 'course_lessons', 'course_batches', 'courses'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
