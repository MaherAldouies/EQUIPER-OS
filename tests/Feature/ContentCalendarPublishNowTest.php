<?php

namespace Tests\Feature;

use App\Models\ContentAsset;
use App\Models\Integration;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContentCalendarPublishNowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
        config(['equiperos.meta.ig_user_id' => '17841400000000000']);
    }

    private function connectMeta(string $organizationId): void
    {
        $integration = Integration::query()->create([
            'organization_id' => $organizationId,
            'provider' => 'meta',
            'status' => 'connected',
        ]);
        $integration->credential()->create(['access_token' => 'test-token']);
    }

    private function socialUser(Organization $organization): User
    {
        // The route sits inside the content.view group AND requires
        // social.manage on the publish-now route itself.
        foreach (['content.view', 'social.manage'] as $key) {
            Permission::query()->firstOrCreate(['key' => $key]);
        }
        $role = Role::factory()->create(['organization_id' => $organization->id]);
        $role->permissions()->attach(Permission::query()->whereIn('key', ['content.view', 'social.manage'])->pluck('id'));

        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_publish_now_calls_meta_and_marks_asset_published(): void
    {
        Http::fake([
            '*/media' => Http::response(['id' => 'CONTAINER1'], 200),
            '*/media_publish' => Http::response(['id' => 'MEDIA1'], 200),
        ]);

        $organization = Organization::factory()->create();
        $this->connectMeta($organization->id);
        $user = $this->socialUser($organization);
        $asset = ContentAsset::factory()->approved()->create([
            'organization_id' => $organization->id,
            'channel' => 'instagram_caption',
            'channel_metadata' => ['image_url' => 'https://example.com/x.jpg'],
        ]);

        $response = $this->actingAs($user)->post(route('content-calendar.publish-now', $asset));

        $response->assertRedirect();
        $asset->refresh();
        $this->assertSame('published', $asset->status);
        $this->assertSame('MEDIA1', $asset->channel_metadata['platform_post_id']);
    }

    public function test_publish_now_requires_social_manage_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $asset = ContentAsset::factory()->approved()->create([
            'organization_id' => $organization->id,
            'channel' => 'instagram_caption',
            'channel_metadata' => ['image_url' => 'https://example.com/x.jpg'],
        ]);

        $response = $this->actingAs($user)->post(route('content-calendar.publish-now', $asset));

        $response->assertStatus(403);
        $this->assertSame('approved', $asset->fresh()->status);
    }
}
