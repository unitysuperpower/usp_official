<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'features',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}
