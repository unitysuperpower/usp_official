<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePayment extends Model
{
    protected $fillable = ['course_enrollment_id', 'method', 'reference', 'amount_minor', 'currency', 'proof_path', 'status', 'review_note', 'reviewed_by', 'reviewed_at', 'expires_at', 'gateway_environment', 'gateway_code', 'gateway_reference', 'review_history'];

    protected $casts = ['amount_minor' => 'integer', 'reviewed_at' => 'datetime', 'expires_at' => 'datetime', 'review_history' => 'array'];

    protected $hidden = ['proof_path'];

    public function enrollment()
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }
}
