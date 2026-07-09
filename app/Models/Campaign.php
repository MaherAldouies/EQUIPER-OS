<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Campaign — Business Ontology, Marketing Domain (PRD F11, manual
 * tracking only in v1.0 — no Advertisement/paid-spend entity until v1.2).
 */
class Campaign extends Model
{
    use HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'name', 'goal', 'utm_campaign_slug', 'status', 'created_by',
    ];

    /**
     * Factory method ensuring CampaignCreated is always recorded —
     * prefer this over Campaign::create() directly so the event is
     * never accidentally skipped.
     */
    public static function createNew(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $campaign = static::query()->create($attributes + ['status' => 'draft']);

            $campaign->recordEvent(eventType: 'CampaignCreated', payload: [
                'name' => $campaign->name,
            ]);

            return $campaign;
        });
    }

    public function contentAssets(): BelongsToMany
    {
        return $this->belongsToMany(ContentAsset::class, 'campaign_content_asset');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'campaign_product');
    }

    /**
     * Business rule (Business Ontology, Campaign entity): "A Campaign
     * cannot be marked 'Completed' while it still has Content Assets in
     * 'Scheduled' (not yet published) state."
     */
    public function complete(): void
    {
        $hasUnpublishedScheduledAssets = $this->contentAssets()
            ->where('status', 'scheduled')
            ->exists();

        if ($hasUnpublishedScheduledAssets) {
            throw new RuntimeException(
                'Cannot complete Campaign: it still has Content Assets in "scheduled" '.
                'state (Business Ontology, Campaign entity Business Rule).'
            );
        }

        DB::transaction(function () {
            $this->forceFill(['status' => 'completed'])->save();
            $this->recordEvent(eventType: 'CampaignCompleted', payload: ['campaign_id' => $this->id]);
        });
    }
}
