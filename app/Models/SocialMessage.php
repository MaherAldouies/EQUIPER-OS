<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * SocialMessage — Social Media Hub epic. A single inbound or outbound
 * message/comment on a connected platform's unified conversation
 * thread. Replies are always human-authored and human-sent from
 * EQUIPER OS — there is no AI-drafted or autonomous reply path here,
 * a deliberate product decision distinct from F6's AI-drafts/human-
 * approves content generation pattern.
 */
class SocialMessage extends Model
{
    use HasFactory, HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'provider', 'message_type', 'external_conversation_id', 'external_message_id',
        'direction', 'from_name', 'body', 'status', 'replied_by', 'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public static function recordInbound(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $message = static::query()->create($attributes + [
                'direction' => 'inbound',
                'status' => 'unread',
            ]);

            $message->recordEvent(eventType: 'SocialMessageReceived', payload: [
                'provider' => $message->provider,
                'external_conversation_id' => $message->external_conversation_id,
            ]);

            return $message;
        });
    }

    public function markReplied(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->forceFill(['status' => 'replied', 'replied_by' => $user->id])->save();

            $this->recordEvent(eventType: 'SocialMessageReplied', payload: [
                'replied_by' => $user->id,
            ]);
        });
    }
}
