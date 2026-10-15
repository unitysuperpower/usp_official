<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalProduct extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['free_features'=>'array','pro_features'=>'array','published'=>'boolean'];
    protected $hidden = ['image_path'];
    public function plans() { return $this->hasMany(ProductPlan::class); }
    public function releases() { return $this->hasMany(ProductRelease::class); }
    public function orders() { return $this->hasMany(ProductOrder::class); }
    public function licenses() { return $this->hasMany(ProductLicense::class); }
}
