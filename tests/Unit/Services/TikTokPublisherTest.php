<?php

namespace Tests\Unit\Services;

use App\Models\ContentAsset;
use App\Models\Integration;
use App\Models\Organization;
use App\Services\Social\TikTokPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TikTokPublisherTest extends TestCase
{
    use RefreshDatabase;

    private function connectedIntegration(string $organizationId): Integration
    {
        $integration = Integration::query()->create([
            'organization_id' => $organizationId,
            'provider' => 'tiktok',
            'status' => 'connected',
        ]);

        $integration->credential()->create(['access_token' => 'token']);

        return $integration;
    }

    public function test_requires_video_url(): void
    {
        $organization = Organization::factory()->create();
        $this->connectedIntegration($organization->id);

        $asset = ContentAsset::factory()->create([
            'organization_id' => $organization->id,
            'channel' => 'tiktok_video',
            'channel_metadata' => [],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('video_url');

        (new TikTokPublisher())->publish($asset);
    }

    public function test_publishes_via_pull_from_url_using_the_stored_credential(): void
    {
        config(['equiperos.tiktok.api_base_url' => 'https://open.tiktokapis.com/v2']);

        $organization = Organization::factory()->create();
        $this->connectedIntegration($organization->id);

        Http::fake([
            '*/post/publish/video/init/' => Http::response(['data' => ['publish_id' => 'v_pub_url~123']], 200),
        ]);

        $asset = ContentAsset::factory()->create([
            'organization_id' => $organization->id,
            'channel' => 'tiktok_video',
            'body' => 'شاهد الفرن الجديد وهو يعمل!',
            'channel_metadata' => ['video_url' => 'https://example.com/video.mp4'],
        ]);

        $publishId = (new TikTokPublisher())->publish($asset);

        $this->assertSame('v_pub_url~123', $publishId);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer token')
            && $request['source_info']['source'] === 'PULL_FROM_URL'
            && $request['source_info']['video_url'] === 'https://example.com/video.mp4'
            && $request['post_info']['title'] === 'شاهد الفرن الجديد وهو يعمل!');
    }

    public function test_refreshes_the_token_and_retries_on_a_live_401(): void
    {
        config(['equiperos.tiktok.api_base_url' => 'https://open.tiktokapis.com/v2', 'equiperos.tiktok.token_url' => 'https://open.tiktokapis.com/v2/oauth/token/']);

        $organization = Organization::factory()->create();
        $integration = Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'tiktok',
            'settings' => ['client_key' => 'key', 'client_secret' => 'secret'],
        ]);
        $integration->credential()->create(['access_token' => 'stale-token', 'refresh_token' => 'refresh-token']);

        Http::fake([
            'https://open.tiktokapis.com/v2/oauth/token/' => Http::response(['access_token' => 'fresh-token', 'expires_in' => 86400], 200),
            '*/post/publish/video/init/' => Http::sequence()
                ->push(['error' => 'expired'], 401)
                ->push(['data' => ['publish_id' => 'v_pub_url~456']], 200),
        ]);

        $asset = ContentAsset::factory()->create([
            'organization_id' => $organization->id,
            'channel' => 'tiktok_video',
            'channel_metadata' => ['video_url' => 'https://example.com/video.mp4'],
        ]);

        $publishId = (new TikTokPublisher())->publish($asset);

        $this->assertSame('v_pub_url~456', $publishId);
        $this->assertSame('fresh-token', $integration->credential->fresh()->access_token);
    }

    public function test_rejects_non_tiktok_channel(): void
    {
        $asset = ContentAsset::factory()->create(['channel' => 'instagram_caption']);

        $this->expectException(RuntimeException::class);

        (new TikTokPublisher())->publish($asset);
    }
}
