<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseLesson extends Model
{
    protected $fillable = ['course_id', 'title', 'position', 'duration_minutes', 'content', 'video_url', 'resource_url', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
