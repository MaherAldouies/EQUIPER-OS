<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Order — Business Ontology, Commerce Domain. Read-only mirror; no
 * business-mutating setter methods (e.g. a hypothetical markShipped())
 * are exposed here on purpose — all writes happen exclusively through
 * SallaSyncService::syncOrder(), which still needs recordEvent() (via
 * HasDomainEvents) to log SallaOrderSynced per the Event Backbone (F1).
 */
class Order extends Model
{
    use HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'salla_order_id', 'salla_raw_payload', 'status',
        'total_amount', 'currency', 'customer_reference', 'attributed_campaign_id',
        'placed_at', 'last_synced_at',
    ];

    protected $casts = [
        'salla_raw_payload' => 'array',
        'total_amount' => 'decimal:2',
        'placed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function lineItems(): HasMany
    {
        return $this->hasMany(OrderLineItem::class);
    }
}
