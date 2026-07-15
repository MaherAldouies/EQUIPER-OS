<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);

        // markEnriched() dispatches ProductWasEnriched, whose listener
        // (queued) dispatches GenerateProductContentJob — fake the
        // queue so this unit test doesn't trigger a real AI generation
        // attempt (no Brand Voice/API key configured here).
        Queue::fake();
    }

    public function test_cannot_mark_enriched_without_category(): void
    {
        $product = Product::factory()->create(['category_id' => null]);

        $this->expectException(RuntimeException::class);

        $product->markEnriched();
    }

    public function test_marking_enriched_with_category_updates_lifecycle_state_and_records_event(): void
    {
        $organization = Organization::factory()->create();
        $category = Category::factory()->create(['organization_id' => $organization->id]);
        $product = Product::factory()->create([
            'organization_id' => $organization->id,
            'category_id' => $category->id,
            'lifecycle_state' => 'draft',
        ]);

        $product->markEnriched();

        $product->refresh();

        $this->assertSame('enriched', $product->lifecycle_state);
        $this->assertNotNull($product->enriched_at);
        $this->assertDatabaseHas('domain_events', [
            'aggregate_id' => $product->id,
            'event_type' => 'ProductEnriched',
        ]);
    }
}
