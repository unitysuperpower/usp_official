<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationQuizAttempt extends Model
{
    protected $fillable = ['education_quiz_id', 'course_enrollment_id', 'answers', 'score', 'passed', 'attempt_number'];

    protected $casts = ['answers' => 'array', 'passed' => 'boolean'];

    public function quiz()
    {
        return $this->belongsTo(EducationQuiz::class, 'education_quiz_id');
    }
}
