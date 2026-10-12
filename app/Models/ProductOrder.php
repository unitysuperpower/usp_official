<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['quote_expires_at'=>'datetime','paid_at'=>'datetime','amount_minor'=>'integer','usd_minor'=>'integer','term_months'=>'integer','site_limit'=>'integer'];
    protected $hidden = [];
    public function product() { return $this->belongsTo(DigitalProduct::class, 'digital_product_id'); }
    public function plan() { return $this->belongsTo(ProductPlan::class, 'product_plan_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function payments() { return $this->hasMany(ProductPayment::class); }
    public function license() { return $this->hasOne(ProductLicense::class); }
}
