<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $t) {
            $t->string('preview_video_url', 2000)->nullable();
            $t->text('instructor_bio')->nullable();
            $t->text('faq')->nullable();
        });
        Schema::create('education_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_enrollment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('course_lesson_id')->constrained()->cascadeOnDelete();
            $t->text('body');
            $t->timestamps();
            $t->unique(['course_enrollment_id', 'course_lesson_id']);
        });
        Schema::create('education_announcements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_batch_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('body');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_announcements');
        Schema::dropIfExists('education_notes');
        Schema::table('courses', fn (Blueprint $t) => $t->dropColumn(['preview_video_url', 'instructor_bio', 'faq']));
    }
};
