<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ContentAsset extends Model
{
    use HasFactory, HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'content_id', 'channel', 'body',
        'channel_metadata', 'status', 'scheduled_for', 'published_at',
    ];

    protected $casts = [
        'channel_metadata' => 'array',
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function approval(): MorphOne
    {
        return $this->morphOne(Approval::class, 'approvable');
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_content_asset');
    }

    /**
     * PRD F10 business rule: "An Asset cannot be scheduled before it
     * reaches 'Approved' state."
     */
    public function schedule(\DateTimeInterface $publishDate): void
    {
        if ($this->status !== 'approved') {
            throw new RuntimeException(
                "Cannot schedule ContentAsset {$this->id}: status is '{$this->status}', ".
                "must be 'approved' first (PRD F10 business rule)."
            );
        }

        DB::transaction(function () use ($publishDate) {
            $this->forceFill([
                'status' => 'scheduled',
                'scheduled_for' => $publishDate,
            ])->save();

            $this->recordEvent(eventType: 'AssetScheduled', payload: [
                'scheduled_for' => $publishDate->format(DATE_ATOM),
            ]);
        });
    }

    /**
     * v1.0 uses manual-confirm publishing (PRD F10): a human clicks
     * "mark as published" after posting manually — no direct social
     * platform API publishing in v1.0.
     */
    public function confirmPublished(): void
    {
        if ($this->status !== 'scheduled') {
            throw new RuntimeException(
                "Cannot confirm publish for ContentAsset {$this->id}: status is ".
                "'{$this->status}', must be 'scheduled' first."
            );
        }

        DB::transaction(function () {
            $this->forceFill([
                'status' => 'published',
                'published_at' => now(),
            ])->save();

            $this->recordEvent(eventType: 'AssetPublished', payload: [
                'published_at' => now()->toIso8601String(),
            ]);
        });
    }
}
