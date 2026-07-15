<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\Integrations;
use App\Models\Integration;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsIntegrationsTest extends TestCase
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

    public function test_user_without_permission_cannot_view_the_page(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        Livewire::actingAs($user)->test(Integrations::class)->assertForbidden();
    }

    public function test_saving_salla_settings_persists_encrypted_and_is_never_redisplayed(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Livewire::actingAs($user)
            ->test(Integrations::class)
            ->set('salla_client_id', 'CLIENT123')
            ->set('salla_client_secret', 'super-secret')
            ->set('salla_webhook_secret', 'webhook-secret')
            ->set('salla_access_token', 'access-token-value')
            ->set('salla_refresh_token', 'refresh-token-value')
            ->call('saveSalla')
            ->assertSet('salla_access_token', '')
            ->assertSet('salla_refresh_token', '')
            ->assertSet('salla_client_secret', '')
            ->assertSet('salla_webhook_secret', '');

        $integration = Integration::query()->where('organization_id', $organization->id)->where('provider', 'salla')->firstOrFail();

        $this->assertSame('CLIENT123', $integration->settings['client_id']);
        $this->assertSame('access-token-value', $integration->credential->access_token);
        $this->assertSame('super-secret', $integration->credential->secrets['client_secret']);
        $this->assertSame('webhook-secret', $integration->credential->secrets['webhook_secret']);

        // Never stored in plaintext anywhere queryable.
        $raw = DB::table('integration_credentials')->where('id', $integration->credential->id)->first();
        $this->assertStringNotContainsString('super-secret', $raw->secrets);
        $this->assertStringNotContainsString('access-token-value', $raw->access_token);
    }

    public function test_blank_fields_do_not_overwrite_previously_saved_secrets(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Livewire::actingAs($user)
            ->test(Integrations::class)
            ->set('salla_access_token', 'first-token')
            ->call('saveSalla');

        // Saving again with the access token left blank should not erase it.
        Livewire::actingAs($user)
            ->test(Integrations::class)
            ->set('salla_client_id', 'UPDATED_CLIENT')
            ->call('saveSalla');

        $integration = Integration::query()->where('organization_id', $organization->id)->where('provider', 'salla')->firstOrFail();

        $this->assertSame('first-token', $integration->credential->access_token);
        $this->assertSame('UPDATED_CLIENT', $integration->settings['client_id']);
    }

    public function test_saving_x_settings_persists_non_secret_and_secret_fields(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->configuratorUser($organization);

        Livewire::actingAs($user)
            ->test(Integrations::class)
            ->set('x_client_id', 'X_CLIENT')
            ->set('x_user_id', '999')
            ->set('x_access_token', 'x-access')
            ->set('x_refresh_token', 'x-refresh')
            ->call('saveX');

        $integration = Integration::query()->where('organization_id', $organization->id)->where('provider', 'x')->firstOrFail();

        $this->assertSame('X_CLIENT', $integration->settings['client_id']);
        $this->assertSame('999', $integration->settings['user_id']);
        $this->assertSame('x-access', $integration->credential->access_token);
        $this->assertSame('x-refresh', $integration->credential->refresh_token);
    }
}
