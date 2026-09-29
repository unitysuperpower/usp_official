<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class EducationSetting extends Model
{
    protected $fillable = ['id', 'manual_methods', 'gateway'];
    protected $casts = ['manual_methods' => 'array', 'gateway' => 'encrypted:array'];
    protected $hidden = ['gateway'];

    public static function current(): ?self
    {
        return Schema::hasTable('education_settings') ? self::find(1) : null;
    }

    public static function gatewayConfig(): array
    {
        return array_replace(config('jazzcash'), self::current()?->gateway ?? []);
    }
}
