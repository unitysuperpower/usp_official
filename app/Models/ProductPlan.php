<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPlan extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['published'=>'boolean','price_minor'=>'integer','term_months'=>'integer','site_limit'=>'integer'];
    protected $hidden = [];
    public function product() { return $this->belongsTo(DigitalProduct::class, 'digital_product_id'); }
}
