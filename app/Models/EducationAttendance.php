<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationAttendance extends Model
{
    protected $fillable = ['education_session_id', 'course_enrollment_id', 'status', 'note', 'marked_by'];

    protected $casts = [];

    protected $table = 'education_attendance';

    public function session()
    {
        return $this->belongsTo(EducationSession::class, 'education_session_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }
}
