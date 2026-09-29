<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationSubmission extends Model
{
    protected $fillable = ['education_assignment_id', 'course_enrollment_id', 'body', 'file_path', 'file_name', 'status', 'score', 'feedback', 'submitted_at', 'graded_at', 'graded_by', 'is_late'];

    protected $casts = ['submitted_at' => 'datetime', 'graded_at' => 'datetime', 'is_late' => 'boolean'];

    protected $hidden = ['file_path'];

    public function assignment()
    {
        return $this->belongsTo(EducationAssignment::class, 'education_assignment_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }
}
