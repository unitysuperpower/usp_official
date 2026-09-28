<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePayment extends Model
{
    protected $fillable = ['course_enrollment_id', 'method', 'reference', 'amount_minor', 'currency', 'proof_path', 'status', 'review_note', 'reviewed_by', 'reviewed_at'];

    protected $casts = ['amount_minor' => 'integer', 'reviewed_at' => 'datetime'];

    protected $hidden = ['proof_path'];

    public function enrollment()
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }
}
