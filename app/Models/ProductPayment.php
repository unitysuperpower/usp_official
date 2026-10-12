<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPayment extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['expires_at'=>'datetime','reviewed_at'=>'datetime'];
    protected $hidden = ['proof_path'];
    public function order() { return $this->belongsTo(ProductOrder::class, 'product_order_id'); }
}
