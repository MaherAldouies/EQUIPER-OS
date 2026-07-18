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

class GoogleOAuthControllerTest extends TestCase
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

    public function test_connect_redirects_to_google_with_expected_params_when_app_configured(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'google',
            'status' => 'configuring',
            'settings' => ['client_id' => 'test-client-id'],
        ]);

        $response = $this->actingAs($user)->get(route('integrations.google.connect', 'google_analytics'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $location);
        $this->assertStringContainsString('client_id=test-client-id', $location);
        $this->assertStringContainsString('analytics.readonly', urldecode($location));
    }

    public function test_connect_redirects_back_with_message_when_app_not_configured(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        $response = $this->actingAs($user)->get(route('integrations.google.connect', 'google_analytics'));

        $response->assertRedirect(route('settings.integrations'));
    }

    public function test_callback_exchanges_code_and_stores_tokens(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'google',
            'status' => 'configuring',
            'settings' => ['client_id' => 'test-client-id'],
        ])->credential()->create(['secrets' => ['client_secret' => 'test-client-secret']]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'oauth-access-token',
                'refresh_token' => 'oauth-refresh-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        // Simulate the session state set by connect().
        $this->actingAs($user)->withSession([
            'google_oauth_state' => 'test-state',
            'google_oauth_provider' => 'google_analytics',
        ])->get(route('integrations.google.callback', ['code' => 'auth-code', 'state' => 'test-state']))
            ->assertRedirect(route('settings.integrations'));

        $integration = Integration::query()->where('organization_id', $organization->id)->where('provider', 'google_analytics')->firstOrFail();

        $this->assertSame('oauth-access-token', $integration->credential->access_token);
        $this->assertSame('oauth-refresh-token', $integration->credential->refresh_token);
        $this->assertNotNull($integration->credential->expires_at);
    }

    public function test_callback_rejects_mismatched_state(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        $response = $this->actingAs($user)->withSession([
            'google_oauth_state' => 'expected-state',
            'google_oauth_provider' => 'google_analytics',
        ])->get(route('integrations.google.callback', ['code' => 'auth-code', 'state' => 'wrong-state']));

        $response->assertRedirect(route('settings.integrations'));
        $this->assertDatabaseCount('integrations', 0);
    }

    public function test_user_without_permission_cannot_connect(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)->get(route('integrations.google.connect', 'google_analytics'));

        $response->assertStatus(403);
    }
}
