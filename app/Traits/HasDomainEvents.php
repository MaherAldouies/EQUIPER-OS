<?php

namespace App\Traits;

use App\Models\DomainEvent;
use App\Services\EventBus\DomainEventPublisher;

/**
 * HasDomainEvents
 *
 * Implements the Transactional Outbox pattern described in the
 * Event-Driven Architecture document (Section 3.2): a model records a
 * domain event in the SAME database transaction as its own state change,
 * by calling recordEvent() from within a DB::transaction() closure.
 *
 * The event is NOT delivered to Redis synchronously here — that is the
 * relay worker's job (see app/Console/Commands/RelayOutboxEvents.php).
 * This is deliberate: it guarantees the event is never lost even if the
 * broker is briefly unavailable, at the cost of near-real-time (not
 * instant) delivery — an explicit, documented tradeoff.
 */
trait HasDomainEvents
{
    /**
     * Record a domain event for this model instance.
     *
     * @param  string  $eventType  Must be pre-registered in the event_catalog table.
     * @param  array  $payload
     * @param  string  $actorType  system | user | ai_agent
     * @param  string|null  $actorId
     * @param  string|null  $causedByEventId  For building causal chains.
     */
    public function recordEvent(
        string $eventType,
        array $payload = [],
        string $actorType = 'system',
        ?string $actorId = null,
        ?string $causedByEventId = null,
    ): DomainEvent {
        return app(DomainEventPublisher::class)->write(
            organizationId: $this->organization_id,
            eventType: $eventType,
            aggregateType: class_basename(static::class),
            aggregateId: $this->id,
            payload: $payload,
            actorType: $actorType,
            actorId: $actorId,
            causedByEventId: $causedByEventId,
        );
    }
}
