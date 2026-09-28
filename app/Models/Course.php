<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['title', 'slug', 'category', 'level', 'mode', 'summary', 'description', 'outcomes', 'prerequisites', 'instructor', 'duration_hours', 'fee_minor', 'currency', 'is_published'];

    protected $casts = ['is_published' => 'boolean', 'fee_minor' => 'integer'];

    public function batches()
    {
        return $this->hasMany(CourseBatch::class);
    }

    public function lessons()
    {
        return $this->hasMany(CourseLesson::class)->orderBy('position')->orderBy('id');
    }
}
