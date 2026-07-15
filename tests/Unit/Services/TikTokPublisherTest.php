<?php

namespace Tests\Unit\Services;

use App\Models\ContentAsset;
use App\Services\Social\TikTokPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TikTokPublisherTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_video_url(): void
    {
        config(['equiperos.tiktok.access_token' => 'token']);

        $asset = ContentAsset::factory()->create([
            'channel' => 'tiktok_video',
            'channel_metadata' => [],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('video_url');

        (new TikTokPublisher())->publish($asset);
    }

    public function test_publishes_via_pull_from_url(): void
    {
        config(['equiperos.tiktok.access_token' => 'token', 'equiperos.tiktok.api_base_url' => 'https://open.tiktokapis.com/v2']);

        Http::fake([
            '*/post/publish/video/init/' => Http::response(['data' => ['publish_id' => 'v_pub_url~123']], 200),
        ]);

        $asset = ContentAsset::factory()->create([
            'channel' => 'tiktok_video',
            'body' => 'شاهد الفرن الجديد وهو يعمل!',
            'channel_metadata' => ['video_url' => 'https://example.com/video.mp4'],
        ]);

        $publishId = (new TikTokPublisher())->publish($asset);

        $this->assertSame('v_pub_url~123', $publishId);

        Http::assertSent(fn ($request) => $request['source_info']['source'] === 'PULL_FROM_URL'
            && $request['source_info']['video_url'] === 'https://example.com/video.mp4'
            && $request['post_info']['title'] === 'شاهد الفرن الجديد وهو يعمل!');
    }

    public function test_rejects_non_tiktok_channel(): void
    {
        $asset = ContentAsset::factory()->create(['channel' => 'instagram_caption']);

        $this->expectException(RuntimeException::class);

        (new TikTokPublisher())->publish($asset);
    }
}
