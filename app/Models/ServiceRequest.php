<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    protected $fillable = [
        'service_id',
        'user_id',
        'name',
        'email',
        'phone',
        'company',
        'message',
        'status',
        'admin_notes',
        'lead_stage', 'priority', 'assigned_to', 'follow_up_on',
    ];

    public const STAGES = ['new', 'contacted', 'qualified', 'won', 'lost'];

    protected $casts = ['follow_up_on' => 'date:Y-m-d'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
