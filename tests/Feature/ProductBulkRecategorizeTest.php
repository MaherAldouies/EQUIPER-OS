<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBulkRecategorizeTest extends TestCase
{
    use RefreshDatabase;

    private function actingUserWithPermissions(array $permissionKeys): array
    {
        $organization = Organization::factory()->create();
        $role = Role::factory()->create(['organization_id' => $organization->id, 'key' => 'marketing_manager']);

        foreach ($permissionKeys as $permissionKey) {
            $permission = Permission::query()->firstOrCreate(['key' => $permissionKey]);
            $role->permissions()->attach($permission->id);
        }

        $user = User::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);
        $user->roles()->attach($role->id);

        return [$organization, $user];
    }

    public function test_bulk_recategorize_updates_all_selected_products(): void
    {
        // The route requires product.view (group middleware) AND
        // product.manage_category (route-specific), matching the real
        // marketing_manager role seeded by OrganizationSeeder.
        [$organization, $user] = $this->actingUserWithPermissions(['product.view', 'product.manage_category']);
        $category = Category::factory()->create(['organization_id' => $organization->id]);
        $products = Product::factory()->count(3)->create(['organization_id' => $organization->id, 'category_id' => null]);

        $response = $this->actingAs($user)->post(route('products.bulk-recategorize'), [
            'product_ids' => $products->pluck('id')->all(),
            'category_id' => $category->id,
        ]);

        $response->assertRedirect();

        foreach ($products as $product) {
            $this->assertSame($category->id, $product->fresh()->category_id);
        }
    }

    public function test_user_without_permission_cannot_reach_bulk_recategorize(): void
    {
        // Has product.view (can list products) but not manage_category.
        [$organization, $user] = $this->actingUserWithPermissions(['product.view']);
        $category = Category::factory()->create(['organization_id' => $organization->id]);
        $product = Product::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)->post(route('products.bulk-recategorize'), [
            'product_ids' => [$product->id],
            'category_id' => $category->id,
        ]);

        $response->assertStatus(403);
        $this->assertNull($product->fresh()->category_id);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->post(route('products.bulk-recategorize'), [
            'product_ids' => [],
            'category_id' => null,
        ]);

        $response->assertRedirect(route('login'));
    }
}
