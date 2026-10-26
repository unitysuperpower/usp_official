<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['title', 'slug', 'category', 'level', 'mode', 'summary', 'description', 'outcomes', 'prerequisites', 'instructor', 'duration_hours', 'fee_minor', 'currency', 'is_published', 'cover_image', 'cover_alt', 'visual_theme', 'is_featured', 'carousel_position', 'preview_video_url', 'instructor_bio', 'faq'];

    protected $casts = ['is_published' => 'boolean', 'is_featured' => 'boolean', 'carousel_position' => 'integer', 'fee_minor' => 'integer'];

    public function batches()
    {
        return $this->hasMany(CourseBatch::class);
    }

    public function instructors()
    {
        return $this->belongsToMany(User::class, 'course_instructors');
    }

    public function lessons()
    {
        return $this->hasMany(CourseLesson::class)->orderBy('position')->orderBy('id');
    }
}
