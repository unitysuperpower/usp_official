<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationQuiz extends Model
{
    protected $fillable = ['course_batch_id', 'title', 'instructions', 'questions', 'pass_percent', 'max_attempts', 'is_published', 'is_required'];

    protected $casts = ['questions' => 'array', 'is_published' => 'boolean', 'is_required' => 'boolean'];

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'course_batch_id');
    }

    public function attempts()
    {
        return $this->hasMany(EducationQuizAttempt::class);
    }
}
