<?php

namespace Tests\Unit\Services;

use App\Models\EventCatalogEntry;
use App\Models\Organization;
use App\Services\EventBus\DomainEventPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DomainEventPublisherTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_unregistered_event_types(): void
    {
        $organization = Organization::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/not registered in the Event Catalog/');

        app(DomainEventPublisher::class)->write(
            organizationId: $organization->id,
            eventType: 'SomeTotallyMadeUpEvent',
            aggregateType: 'Product',
            aggregateId: (string) \Illuminate\Support\Str::uuid(),
        );
    }

    public function test_writes_registered_event_types(): void
    {
        $organization = Organization::factory()->create();
        EventCatalogEntry::query()->create([
            'event_type' => 'TestEventHappened',
            'aggregate_type' => 'Product',
            'owning_domain' => 'Product Domain',
            'description' => 'Test.',
            'requires_approval_downstream' => false,
        ]);

        $event = app(DomainEventPublisher::class)->write(
            organizationId: $organization->id,
            eventType: 'TestEventHappened',
            aggregateType: 'Product',
            aggregateId: (string) \Illuminate\Support\Str::uuid(),
            payload: ['foo' => 'bar'],
        );

        $this->assertDatabaseHas('domain_events', [
            'id' => $event->id,
            'event_type' => 'TestEventHappened',
        ]);
        $this->assertNull($event->published_at);
    }
}
