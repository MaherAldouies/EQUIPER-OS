<?php

namespace Tests\Unit\Services;

use App\Models\ContentAsset;
use App\Models\Integration;
use App\Services\Social\XPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class XPublisherTest extends TestCase
{
    use RefreshDatabase;

    private function withXCredential(string $organizationId): void
    {
        config(['equiperos.x.api_base_url' => 'https://api.x.com/2']);

        $integration = Integration::query()->create([
            'organization_id' => $organizationId,
            'provider' => 'x',
            'status' => 'connected',
        ]);

        $integration->credential()->create([
            'access_token' => 'valid-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->addHour(),
        ]);
    }

    public function test_publishes_a_tweet(): void
    {
        Http::fake([
            '*/tweets' => Http::response(['data' => ['id' => 'TWEET123', 'text' => 'hi']], 200),
        ]);

        $asset = ContentAsset::factory()->create([
            'organization_id' => $orgId = \App\Models\Organization::factory()->create()->id,
            'channel' => 'x_post',
            'body' => 'New product just dropped!',
        ]);
        $this->withXCredential($orgId);

        $postId = (new XPublisher())->publish($asset);

        $this->assertSame('TWEET123', $postId);
        Http::assertSent(fn ($request) => $request['text'] === 'New product just dropped!');
    }

    public function test_rejects_non_x_channel(): void
    {
        $asset = ContentAsset::factory()->create(['channel' => 'instagram_caption']);

        $this->expectException(RuntimeException::class);

        (new XPublisher())->publish($asset);
    }
}
