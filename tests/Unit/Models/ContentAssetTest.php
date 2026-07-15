<?php

namespace Tests\Unit\Models;

use App\Models\ContentAsset;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ContentAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
    }

    public function test_cannot_schedule_unless_approved(): void
    {
        $asset = ContentAsset::factory()->create(['status' => 'generated']);

        $this->expectException(RuntimeException::class);

        $asset->schedule(now()->addDay());
    }

    public function test_schedule_sets_scheduled_state(): void
    {
        $asset = ContentAsset::factory()->approved()->create();

        $publishDate = now()->addDay();
        $asset->schedule($publishDate);

        $this->assertSame('scheduled', $asset->fresh()->status);
    }

    public function test_cannot_confirm_published_unless_scheduled(): void
    {
        $asset = ContentAsset::factory()->approved()->create();

        $this->expectException(RuntimeException::class);

        $asset->confirmPublished();
    }

    public function test_confirm_published_sets_published_state_and_timestamp(): void
    {
        $asset = ContentAsset::factory()->scheduled()->create();

        $asset->confirmPublished();
        $asset->refresh();

        $this->assertSame('published', $asset->status);
        $this->assertNotNull($asset->published_at);
    }
}
