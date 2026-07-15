<?php

namespace Tests\Unit\Models;

use App\Models\Campaign;
use App\Models\ContentAsset;
use App\Models\Organization;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
    }

    public function test_cannot_complete_campaign_with_scheduled_content_assets(): void
    {
        $organization = Organization::factory()->create();
        $campaign = Campaign::createNew(['organization_id' => $organization->id, 'name' => 'Launch']);
        $asset = ContentAsset::factory()->scheduled()->create(['organization_id' => $organization->id]);
        $campaign->contentAssets()->attach($asset->id);

        $this->expectException(RuntimeException::class);

        $campaign->complete();
    }

    public function test_completes_when_no_scheduled_assets_remain(): void
    {
        $organization = Organization::factory()->create();
        $campaign = Campaign::createNew(['organization_id' => $organization->id, 'name' => 'Launch']);
        $asset = ContentAsset::factory()->create(['organization_id' => $organization->id, 'status' => 'published']);
        $campaign->contentAssets()->attach($asset->id);

        $campaign->complete();

        $this->assertSame('completed', $campaign->fresh()->status);
    }
}
