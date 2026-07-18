<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TikTokOAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function configuratorUser(Organization $organization): User
    {
        $permission = Permission::query()->firstOrCreate(['key' => 'integration.configure']);
        $role = Role::factory()->create(['organization_id' => $organization->id]);
        $role->permissions()->attach($permission->id);

        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_connect_redirects_to_tiktok_with_expected_params_when_configured(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'tiktok',
            'status' => 'configuring',
            'settings' => ['client_key' => 'test-client-key'],
        ]);

        $response = $this->actingAs($user)->get(route('integrations.tiktok.connect'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://www.tiktok.com/v2/auth/authorize/?', $location);
        $this->assertStringContainsString('client_key=test-client-key', $location);
        $this->assertStringContainsString('video.publish', urldecode($location));
        $this->assertStringContainsString('code_challenge=', $location);
        $this->assertStringContainsString('code_challenge_method=S256', $location);
    }

    public function test_connect_redirects_back_with_message_when_not_configured(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        $response = $this->actingAs($user)->get(route('integrations.tiktok.connect'));

        $response->assertRedirect(route('settings.integrations'));
    }

    public function test_callback_exchanges_code_and_stores_tokens(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'tiktok',
            'status' => 'configuring',
            'settings' => ['client_key' => 'test-client-key'],
        ])->credential()->create(['secrets' => ['client_secret' => 'test-client-secret']]);

        Http::fake([
            'https://open.tiktokapis.com/v2/oauth/token/' => Http::response([
                'access_token' => 'tiktok-access-token',
                'refresh_token' => 'tiktok-refresh-token',
                'expires_in' => 86400,
            ], 200),
        ]);

        $this->actingAs($user)->withSession(['tiktok_oauth_state' => 'test-state', 'tiktok_oauth_code_verifier' => 'test-verifier'])
            ->get(route('integrations.tiktok.callback', ['code' => 'auth-code', 'state' => 'test-state']))
            ->assertRedirect(route('settings.integrations'));

        $integration = Integration::query()->where('organization_id', $organization->id)->where('provider', 'tiktok')->firstOrFail();

        $this->assertSame('tiktok-access-token', $integration->credential->access_token);
        $this->assertSame('tiktok-refresh-token', $integration->credential->refresh_token);

        Http::assertSent(fn ($request) => $request->url() === 'https://open.tiktokapis.com/v2/oauth/token/'
            && $request['code_verifier'] === 'test-verifier');
    }

    public function test_callback_rejects_mismatched_state(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        $response = $this->actingAs($user)->withSession(['tiktok_oauth_state' => 'expected-state'])
            ->get(route('integrations.tiktok.callback', ['code' => 'auth-code', 'state' => 'wrong-state']));

        $response->assertRedirect(route('settings.integrations'));
        $this->assertDatabaseCount('integrations', 0);
    }

    public function test_user_without_permission_cannot_connect(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)->get(route('integrations.tiktok.connect'));

        $response->assertStatus(403);
    }
}
