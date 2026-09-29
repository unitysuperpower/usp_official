<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationAssignment extends Model
{
    protected $fillable = ['course_batch_id', 'title', 'instructions', 'due_at', 'allow_late', 'is_published', 'is_required', 'pass_percent'];

    protected $casts = ['due_at' => 'datetime', 'allow_late' => 'boolean', 'is_published' => 'boolean', 'is_required' => 'boolean'];

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'course_batch_id');
    }

    public function submissions()
    {
        return $this->hasMany(EducationSubmission::class);
    }
}
