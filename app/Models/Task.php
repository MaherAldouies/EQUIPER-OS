<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'title', 'description', 'assigned_to',
        'related_approval_id', 'status', 'due_at', 'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class, 'related_approval_id');
    }

    public function markCompleted(): void
    {
        $this->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();

        $this->recordEvent(eventType: 'TaskCompleted', payload: ['task_id' => $this->id]);
    }
}
