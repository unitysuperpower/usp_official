<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    public const STATUSES = ['pending', 'in_progress', 'awaiting_approval', 'changes_requested', 'completed'];

    protected $fillable = ['title', 'description', 'due_on', 'status', 'requires_approval', 'approval_note', 'approved_at', 'approved_by'];

    protected $casts = ['due_on' => 'date:Y-m-d', 'requires_approval' => 'boolean', 'approved_at' => 'datetime'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
