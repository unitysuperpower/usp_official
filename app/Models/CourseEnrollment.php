<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    protected $fillable = ['user_id', 'course_batch_id', 'status', 'phone', 'motivation', 'review_note', 'fee_minor', 'currency', 'accepted_at', 'enrolled_at', 'completed_at', 'certificate_code', 'certificate_name', 'certificate_course'];

    protected $casts = ['fee_minor' => 'integer', 'accepted_at' => 'datetime', 'enrolled_at' => 'datetime', 'completed_at' => 'datetime'];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'course_batch_id');
    }

    public function payments()
    {
        return $this->hasMany(CoursePayment::class)->latest();
    }
}
