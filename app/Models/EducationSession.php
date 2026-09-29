<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationSession extends Model
{
    protected $fillable = ['course_batch_id', 'title', 'starts_at', 'status', 'notes'];

    protected $casts = ['starts_at' => 'datetime'];

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'course_batch_id');
    }

    public function attendance()
    {
        return $this->hasMany(EducationAttendance::class);
    }
}
