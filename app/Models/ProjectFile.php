<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    protected $fillable = ['uploaded_by', 'original_name', 'path', 'size'];

    protected $hidden = ['path'];
}
