<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['usd_pkr_rate'=>'decimal:4'];
    protected $hidden = [];
    
}
