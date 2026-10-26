<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalProduct extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['free_features'=>'array','pro_features'=>'array','published'=>'boolean','screenshots'=>'array','faq'=>'array','requirements'=>'array','activation_steps'=>'array','sort_order'=>'integer'];
    protected $appends = ['icon_url'];

    public function getIconUrlAttribute(): ?string
    {
        return $this->image_path ? \App\Support\Theme::publicPath($this->image_path) : null;
    }
    protected $hidden = ['image_path'];
    public function plans() { return $this->hasMany(ProductPlan::class); }
    public function releases() { return $this->hasMany(ProductRelease::class); }
    public function orders() { return $this->hasMany(ProductOrder::class); }
    public function licenses() { return $this->hasMany(ProductLicense::class); }
}
