<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseActivation extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['token_secret'=>'encrypted','last_checked_at'=>'datetime'];
    protected $hidden = ['site_hash','token_secret'];
    public function license() { return $this->belongsTo(ProductLicense::class, 'product_license_id'); }
}
