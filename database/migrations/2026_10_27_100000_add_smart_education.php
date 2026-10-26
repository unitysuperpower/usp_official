<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff accounts that teach a course; they run only that course's classrooms.
        Schema::create('course_instructors', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['course_id', 'user_id']);
        });
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->foreignId('last_lesson_id')->nullable()->constrained('course_lessons')->nullOnDelete();
            $table->timestamp('last_active_at')->nullable()->index();
        });
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->string('section', 120)->nullable(); // optional module heading in the curriculum
        });
        // Days on which a student opened or finished learning material; drives streaks and inactivity.
        Schema::create('education_activity_days', function (Blueprint $table) {
            $table->foreignId('course_enrollment_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->primary(['course_enrollment_id', 'day']);
        });
        // One row per reminder sent, so scheduled reminders never repeat.
        Schema::create('education_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->unique();
            $table->foreignId('course_enrollment_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_reminders');
        Schema::dropIfExists('education_activity_days');
        Schema::table('course_lessons', fn (Blueprint $table) => $table->dropColumn('section'));
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_lesson_id');
            $table->dropColumn('last_active_at');
        });
        Schema::dropIfExists('course_instructors');
    }
};
