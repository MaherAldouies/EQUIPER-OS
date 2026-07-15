<?php

namespace Tests\Unit\Services;

use App\Models\ContentAsset;
use App\Services\Social\MetaPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MetaPublisherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'equiperos.meta.ig_user_id' => '17841400000000000',
            'equiperos.meta.page_id' => '102290129340398',
            'equiperos.meta.access_token' => 'test-page-token',
        ]);
    }

    public function test_instagram_publish_requires_image_url(): void
    {
        $asset = ContentAsset::factory()->create([
            'channel' => 'instagram_caption',
            'body' => 'Caption text',
            'channel_metadata' => [],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('image_url');

        (new MetaPublisher())->publish($asset);
    }

    public function test_instagram_publish_does_container_then_publish_two_step_call(): void
    {
        Http::fake([
            '*/17841400000000000/media' => Http::response(['id' => 'CONTAINER123'], 200),
            '*/17841400000000000/media_publish' => Http::response(['id' => 'MEDIA456'], 200),
        ]);

        $asset = ContentAsset::factory()->create([
            'channel' => 'instagram_caption',
            'body' => 'New oven just landed!',
            'channel_metadata' => ['image_url' => 'https://example.com/oven.jpg'],
        ]);

        $postId = (new MetaPublisher())->publish($asset);

        $this->assertSame('MEDIA456', $postId);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/media')
            && ! str_contains($request->url(), 'media_publish')
            && $request['image_url'] === 'https://example.com/oven.jpg'
            && $request['caption'] === 'New oven just landed!');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/media_publish')
            && $request['creation_id'] === 'CONTAINER123');
    }

    public function test_facebook_publish_posts_to_feed_when_no_image(): void
    {
        Http::fake([
            '*/102290129340398/feed' => Http::response(['id' => 'FB789'], 200),
        ]);

        $asset = ContentAsset::factory()->create([
            'channel' => 'facebook_post',
            'body' => 'Big announcement!',
            'channel_metadata' => [],
        ]);

        $postId = (new MetaPublisher())->publish($asset);

        $this->assertSame('FB789', $postId);
    }

    public function test_unsupported_channel_throws(): void
    {
        $asset = ContentAsset::factory()->create(['channel' => 'x_tweet']);

        $this->expectException(RuntimeException::class);

        (new MetaPublisher())->publish($asset);
    }
}
