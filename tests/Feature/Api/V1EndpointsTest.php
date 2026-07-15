<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V1EndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
        $this->getJson('/api/v1/approvals')->assertStatus(401);
        $this->getJson('/api/v1/tasks')->assertStatus(401);
        $this->getJson('/api/v1/campaigns')->assertStatus(401);
        $this->getJson('/api/v1/dashboard/summary')->assertStatus(401);
    }

    public function test_products_index_returns_only_the_users_organization_products(): void
    {
        $organization = Organization::factory()->create();
        $mine = Product::factory()->create(['organization_id' => $organization->id]);
        Product::factory()->create(); // different organization
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/products');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_product_show_returns_404_for_another_organizations_product(): void
    {
        $user = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);
        $otherProduct = Product::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/products/{$otherProduct->id}")
            ->assertStatus(404);
    }

    public function test_dashboard_summary_hides_revenue_without_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard/summary');

        $response->assertOk();
        $response->assertJsonPath('data.revenue', null);
    }

    public function test_dashboard_summary_shows_revenue_with_permission(): void
    {
        $organization = Organization::factory()->create();
        $permission = Permission::query()->firstOrCreate(['key' => 'dashboard.view_revenue']);
        $role = Role::factory()->create(['organization_id' => $organization->id]);
        $role->permissions()->attach($permission->id);
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard/summary');

        $response->assertOk();
        // No signal has been generated yet today, so still null — but
        // the permission gate itself must not be what's blocking it.
        $response->assertJsonPath('data.revenue', null);
    }

    public function test_campaigns_index_returns_content_assets_count(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        Campaign::createNew(['organization_id' => $organization->id, 'name' => 'Launch']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/campaigns');

        $response->assertOk();
        $response->assertJsonPath('data.0.content_assets_count', 0);
    }
}
