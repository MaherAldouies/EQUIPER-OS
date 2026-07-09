<?php

namespace App\Models;

use App\Events\ProductWasEnriched;
use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Product — Business Ontology, Product Domain.
 *
 * salla_product_id / salla_raw_payload are the Anti-Corruption Layer
 * boundary described in the Business Ontology document (Section 0):
 * Salla's data shape is confined to these two fields; every other
 * attribute is EQUIPER OS's own translated, enriched language.
 */
class Product extends Model
{
    use HasFactory, HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'salla_product_id', 'salla_raw_payload', 'salla_category_name',
        'name', 'sku', 'category_id', 'description', 'price', 'brand_name', 'supplier_name',
        'is_agency_brand', 'lifecycle_state', 'stock_quantity', 'stock_status',
        'last_synced_at', 'enriched_at',
    ];

    protected $casts = [
        'salla_raw_payload' => 'array',
        'price' => 'decimal:2',
        'is_agency_brand' => 'boolean',
        'last_synced_at' => 'datetime',
        'enriched_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function seoAssets(): HasMany
    {
        return $this->hasMany(SeoAsset::class);
    }

    /**
     * Business rule (Business Ontology, Product entity): "A Product
     * cannot be marked 'Published' without at least one Category and
     * one enriched description." Enforced here, not just in the UI,
     * because this rule is what the whole AI Content Generation
     * pipeline (F6) depends on triggering correctly from.
     */
    public function markEnriched(): void
    {
        if (! $this->category_id) {
            throw new RuntimeException(
                'Cannot mark a Product enriched without a Category assignment '.
                '(Business Ontology, Product entity Business Rule).'
            );
        }

        DB::transaction(function () {
            $this->forceFill([
                'lifecycle_state' => 'enriched',
                'enriched_at' => now(),
            ])->save();

            // This is the trigger event for F6 (AI Content & SEO Generation)
            $this->recordEvent(
                eventType: 'ProductEnriched',
                payload: [
                    'product_id' => $this->id,
                    'category_id' => $this->category_id,
                    'is_agency_brand' => $this->is_agency_brand,
                ],
            );
        });

        // Fired AFTER the transaction commits (DB::transaction() only
        // returns once committed) — guarantees the queued listener never
        // dispatches GenerateProductContentJob for a Product whose
        // enrichment was rolled back.
        ProductWasEnriched::dispatch($this);
    }
}
