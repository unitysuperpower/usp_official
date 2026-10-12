<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductLicense extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['key_secret'=>'encrypted','expires_at'=>'datetime','site_limit'=>'integer'];
    protected $hidden = ['key_hash','key_secret','admin_note'];
    public function product() { return $this->belongsTo(DigitalProduct::class, 'digital_product_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function activations() { return $this->hasMany(LicenseActivation::class); }
    public function usable(): bool { return $this->status === 'active' && $this->expires_at->isFuture(); }
}
