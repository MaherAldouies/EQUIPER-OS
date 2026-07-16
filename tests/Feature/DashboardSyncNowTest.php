<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSyncNowTest extends TestCase
{
    use RefreshDatabase;

    private function actingUserWithPermissions(array $permissionKeys): array
    {
        $organization = Organization::factory()->create();
        $role = Role::factory()->create(['organization_id' => $organization->id]);

        foreach ($permissionKeys as $permissionKey) {
            $permission = Permission::query()->firstOrCreate(['key' => $permissionKey]);
            $role->permissions()->attach($permission->id);
        }

        $user = User::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);
        $user->roles()->attach($role->id);

        return [$organization, $user];
    }

    public function test_sync_now_recomputes_signals_for_the_users_organization(): void
    {
        $this->seed(EventCatalogSeeder::class);
        [$organization, $user] = $this->actingUserWithPermissions(['integration.configure']);

        Order::query()->create([
            'organization_id' => $organization->id,
            'salla_order_id' => '1',
            'status' => 'completed',
            'total_amount' => 150,
            'currency' => 'SAR',
            'placed_at' => now(),
            'last_synced_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('dashboard.sync-now'));

        $response->assertRedirect();
        $this->assertDatabaseHas('analytics_signals', [
            'organization_id' => $organization->id,
            'metric_key' => 'daily_revenue',
            'source' => 'salla',
        ]);
    }

    public function test_user_without_permission_cannot_trigger_sync(): void
    {
        [, $user] = $this->actingUserWithPermissions([]);

        $response = $this->actingAs($user)->post(route('dashboard.sync-now'));

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->post(route('dashboard.sync-now'));

        $response->assertRedirect(route('login'));
    }
}
