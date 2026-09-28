<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseBatch extends Model
{
    protected $fillable = ['course_id', 'name', 'starts_on', 'ends_on', 'applications_close_on', 'capacity', 'schedule', 'location', 'meeting_url', 'is_open'];

    protected $casts = ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d', 'applications_close_on' => 'date:Y-m-d', 'is_open' => 'boolean'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }
}
