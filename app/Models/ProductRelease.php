<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRelease extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['published'=>'boolean'];
    protected $hidden = ['package_path'];
    public function product() { return $this->belongsTo(DigitalProduct::class, 'digital_product_id'); }
}
