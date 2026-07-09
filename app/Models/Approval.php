<?php

namespace App\Models;

use App\Events\ApprovalWasDecided;
use App\Events\ApprovalWasRequested;
use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * Approval — Business Ontology, Workflow Domain. The core safety gate:
 * "no Content Asset may be published without an 'Approved' Approval
 * record" (PRD F7 business rule).
 */
class Approval extends Model
{
    use HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'approvable_type', 'approvable_id', 'status',
        'requested_for_role', 'decided_by', 'rejection_reason',
        'expires_at', 'decided_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Requests approval for any approvable entity and, per PRD F13,
     * automatically creates a corresponding Task assigned to the
     * appropriate role.
     */
    public static function requestFor(Model $approvable, string $roleKey = 'marketing_manager', int $expiresInHours = 72): self
    {
        $approval = DB::transaction(function () use ($approvable, $roleKey, $expiresInHours) {
            $role = Role::query()
                ->where('organization_id', $approvable->organization_id)
                ->where('key', $roleKey)
                ->first();

            $approval = static::query()->create([
                'organization_id' => $approvable->organization_id,
                'approvable_type' => get_class($approvable),
                'approvable_id' => $approvable->id,
                'status' => 'pending',
                'requested_for_role' => $role?->id,
                'expires_at' => now()->addHours($expiresInHours),
            ]);

            $approval->recordEvent(eventType: 'ApprovalRequested', payload: [
                'approvable_type' => $approval->approvable_type,
                'approvable_id' => $approval->approvable_id,
            ]);

            $task = Task::query()->create([
                'organization_id' => $approvable->organization_id,
                'title' => 'مراجعة محتوى بحاجة إلى اعتماد',
                'description' => "يرجى مراجعة {$approval->approvable_type} #{$approval->approvable_id}",
                'related_approval_id' => $approval->id,
                'status' => 'created',
                'due_at' => $approval->expires_at,
            ]);

            $task->recordEvent(eventType: 'TaskCreated', payload: [
                'related_approval_id' => $approval->id,
            ]);

            return $approval;
        });

        // Dispatched after commit — triggers SendApprovalRequestedNotification.
        ApprovalWasRequested::dispatch($approval);

        return $approval;
    }
    public function approve(User $decider): void
    {
        DB::transaction(function () use ($decider) {
            $this->forceFill([
                'status' => 'approved',
                'decided_by' => $decider->id,
                'decided_at' => now(),
            ])->save();

            $this->recordEvent(eventType: 'ApprovalGranted', payload: [
                'decided_by' => $decider->id,
            ]);

            // Propagate to the underlying entity's own status field, since
            // F10's schedule() requires status = 'approved' on ContentAsset,
            // and SEO Assets move from 'generated' to 'reviewed' likewise.
            $approvable = $this->approvable;
            if ($approvable && in_array('status', $approvable->getFillable(), true)) {
                $newStatus = $approvable instanceof ContentAsset ? 'approved' : 'reviewed';
                $approvable->forceFill(['status' => $newStatus])->save();
            }

            // Any Task linked to this Approval is done once decided.
            Task::query()
                ->where('related_approval_id', $this->id)
                ->where('status', '!=', 'completed')
                ->get()
                ->each(fn (Task $task) => $task->markCompleted());
        });

        ApprovalWasDecided::dispatch($this);
    }

    public function reject(User $decider, string $reason): void
    {
        DB::transaction(function () use ($decider, $reason) {
            $this->forceFill([
                'status' => 'rejected',
                'decided_by' => $decider->id,
                'decided_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->recordEvent(eventType: 'ApprovalRejected', payload: [
                'decided_by' => $decider->id,
                'reason' => $reason,
            ]);

            Task::query()
                ->where('related_approval_id', $this->id)
                ->where('status', '!=', 'completed')
                ->get()
                ->each(fn (Task $task) => $task->markCompleted());
        });

        ApprovalWasDecided::dispatch($this);
    }
}
