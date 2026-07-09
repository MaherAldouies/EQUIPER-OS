<?php

namespace App\Services\EventBus;

use App\Models\DomainEvent;
use App\Models\EventCatalogEntry;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * DomainEventPublisher
 *
 * The single writer of the Event Store / Outbox table. Enforces the
 * Event-Driven Architecture document's governance rule (Section 3.3):
 * "an unregistered event type cannot be published."
 *
 * This class only WRITES the event row (within whatever transaction the
 * caller is already in). Delivery to Redis Streams happens separately,
 * via the outbox relay worker — see RelayOutboxEvents console command.
 */
class DomainEventPublisher
{
    public function write(
        string $organizationId,
        string $eventType,
        string $aggregateType,
        string $aggregateId,
        array $payload = [],
        string $actorType = 'system',
        ?string $actorId = null,
        ?string $causedByEventId = null,
    ): DomainEvent {
        if (! EventCatalogEntry::query()->whereKey($eventType)->exists()) {
            throw new RuntimeException(
                "Event type [{$eventType}] is not registered in the Event Catalog. ".
                'Register it via EventCatalogEntry before publishing (Event-Driven '.
                'Architecture document, Section 3.3 — this is a deliberate hard stop, '.
                'not a bug: unregistered events are how event shape drift happens silently.'
            );
        }

        return DomainEvent::query()->create([
            'id' => Str::uuid(),
            'organization_id' => $organizationId,
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'payload' => $payload,
            'caused_by_event_id' => $causedByEventId,
            'published_at' => null, // outbox relay will pick this up
            'occurred_at' => now(),
        ]);
    }
}
