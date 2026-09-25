<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebPushSubscription extends Model
{
    protected $fillable = ['user_id', 'endpoint_hash', 'endpoint', 'public_key', 'auth_token', 'session_hash'];
    protected $hidden = ['endpoint', 'public_key', 'auth_token', 'session_hash'];
}
