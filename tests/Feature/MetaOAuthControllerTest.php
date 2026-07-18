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

class MetaOAuthControllerTest extends TestCase
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

    public function test_connect_redirects_to_facebook_with_expected_params_when_configured(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'meta',
            'status' => 'configuring',
            'settings' => ['client_id' => 'test-app-id'],
        ]);

        $response = $this->actingAs($user)->get(route('integrations.meta.connect'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://www.facebook.com/v23.0/dialog/oauth?', $location);
        $this->assertStringContainsString('client_id=test-app-id', $location);
        $this->assertStringContainsString('instagram_content_publish', urldecode($location));
    }

    public function test_connect_redirects_back_with_message_when_not_configured(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        $response = $this->actingAs($user)->get(route('integrations.meta.connect'));

        $response->assertRedirect(route('settings.integrations'));
    }

    public function test_callback_auto_saves_when_exactly_one_page(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'meta',
            'status' => 'configuring',
            'settings' => ['client_id' => 'test-app-id'],
        ])->credential()->create(['secrets' => ['app_secret' => 'test-app-secret']]);

        Http::fake([
            'graph.facebook.com/*/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-lived-token'], 200)
                ->push(['access_token' => 'long-lived-token', 'expires_in' => 5184000], 200),
            'graph.facebook.com/*/me/accounts*' => Http::response([
                'data' => [
                    ['id' => 'PAGE1', 'name' => 'Equiper Store', 'access_token' => 'page-token-1'],
                ],
            ], 200),
            'graph.facebook.com/*/PAGE1*' => Http::response([
                'instagram_business_account' => ['id' => 'IG1'],
            ], 200),
        ]);

        $this->actingAs($user)->withSession(['meta_oauth_state' => 'test-state'])
            ->get(route('integrations.meta.callback', ['code' => 'auth-code', 'state' => 'test-state']))
            ->assertRedirect(route('settings.integrations'));

        $integration = Integration::query()->where('organization_id', $organization->id)->where('provider', 'meta')->firstOrFail();

        $this->assertSame('PAGE1', $integration->settings['page_id']);
        $this->assertSame('IG1', $integration->settings['ig_user_id']);
        $this->assertSame('page-token-1', $integration->credential->access_token);
        $this->assertSame('connected', $integration->status);
    }

    public function test_callback_redirects_to_picker_when_multiple_pages(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'meta',
            'status' => 'configuring',
            'settings' => ['client_id' => 'test-app-id'],
        ])->credential()->create(['secrets' => ['app_secret' => 'test-app-secret']]);

        Http::fake([
            'graph.facebook.com/*/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-lived-token'], 200)
                ->push(['access_token' => 'long-lived-token', 'expires_in' => 5184000], 200),
            'graph.facebook.com/*/me/accounts*' => Http::response([
                'data' => [
                    ['id' => 'PAGE1', 'name' => 'Equiper Store', 'access_token' => 'page-token-1'],
                    ['id' => 'PAGE2', 'name' => 'Equiper Wholesale', 'access_token' => 'page-token-2'],
                ],
            ], 200),
            'graph.facebook.com/*/PAGE1*' => Http::response(['instagram_business_account' => ['id' => 'IG1']], 200),
            'graph.facebook.com/*/PAGE2*' => Http::response([], 200),
        ]);

        $this->actingAs($user)->withSession(['meta_oauth_state' => 'test-state'])
            ->get(route('integrations.meta.callback', ['code' => 'auth-code', 'state' => 'test-state']))
            ->assertRedirect(route('integrations.meta.pick-page'));

        $this->assertDatabaseMissing('integrations', ['provider' => 'meta', 'organization_id' => $organization->id, 'status' => 'connected']);
    }

    public function test_select_page_persists_the_chosen_page(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'meta',
            'status' => 'configuring',
        ]);

        $pages = [
            ['id' => 'PAGE1', 'name' => 'Equiper Store', 'access_token' => 'page-token-1', 'ig_user_id' => 'IG1'],
            ['id' => 'PAGE2', 'name' => 'Equiper Wholesale', 'access_token' => 'page-token-2', 'ig_user_id' => null],
        ];

        $this->actingAs($user)->withSession(['meta_oauth_pages' => $pages])
            ->post(route('integrations.meta.select-page'), ['page_id' => 'PAGE2'])
            ->assertRedirect(route('settings.integrations'));

        $integration = Integration::query()->where('organization_id', $organization->id)->where('provider', 'meta')->firstOrFail();

        $this->assertSame('PAGE2', $integration->settings['page_id']);
        $this->assertSame('page-token-2', $integration->credential->access_token);
    }

    public function test_callback_rejects_mismatched_state(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        $response = $this->actingAs($user)->withSession(['meta_oauth_state' => 'expected-state'])
            ->get(route('integrations.meta.callback', ['code' => 'auth-code', 'state' => 'wrong-state']));

        $response->assertRedirect(route('settings.integrations'));
        $this->assertDatabaseCount('integrations', 0);
    }

    public function test_user_without_permission_cannot_connect(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)->get(route('integrations.meta.connect'));

        $response->assertStatus(403);
    }
}
