<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ProductWasEnriched
 *
 * NOTE on architecture: this is a Laravel-native, in-process event —
 * distinct from the durable DomainEvent ("ProductEnriched") written to
 * the Event Store/Outbox by Product::markEnriched(). The DomainEvent is
 * the cross-process, audited, replayable record (consumed by
 * ConsumeProductEnrichedEvents via Redis Streams for any out-of-process
 * subscriber). This Laravel event is the lightweight, same-deployment
 * signal used to trigger queued Jobs/Listeners within this monolith
 * without round-tripping through Redis. Both are fired from the same
 * call site (Product::markEnriched()) — they serve different purposes,
 * not duplicate ones.
 */
class ProductWasEnriched
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Product $product,
    ) {}
}
