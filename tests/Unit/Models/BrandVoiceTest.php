<?php

namespace Tests\Unit\Models;

use App\Models\BrandVoice;
use App\Models\Organization;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandVoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
    }

    public function test_activating_supersedes_previously_active_brand_voice(): void
    {
        $organization = Organization::factory()->create();
        $original = BrandVoice::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);
        $revised = BrandVoice::factory()->create(['organization_id' => $organization->id, 'status' => 'draft']);

        $revised->activate();

        $this->assertSame('superseded', $original->fresh()->status);
        $this->assertSame('active', $revised->fresh()->status);

        // Only one Active Brand Voice per organization at a time.
        $this->assertSame(
            1,
            BrandVoice::query()->where('organization_id', $organization->id)->where('status', 'active')->count()
        );
    }

    public function test_activating_does_not_affect_other_organizations(): void
    {
        $otherOrgActive = BrandVoice::factory()->create(['status' => 'active']);

        $organization = Organization::factory()->create();
        $mine = BrandVoice::factory()->create(['organization_id' => $organization->id, 'status' => 'draft']);
        $mine->activate();

        $this->assertSame('active', $otherOrgActive->fresh()->status);
    }
}
