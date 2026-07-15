<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Products\Index;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_products_for_the_users_organization_only(): void
    {
        $organization = Organization::factory()->create();
        $mine = Product::factory()->create(['organization_id' => $organization->id, 'name' => 'My Product']);
        Product::factory()->create(['name' => 'Someone Elses Product']); // different org

        $user = User::factory()->create(['organization_id' => $organization->id]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('My Product')
            ->assertDontSee('Someone Elses Product');
    }

    public function test_bulk_recategorize_requires_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $category = Category::factory()->create(['organization_id' => $organization->id]);
        $product = Product::factory()->create(['organization_id' => $organization->id]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selected', [$product->id])
            ->set('bulkCategoryId', $category->id)
            ->call('bulkRecategorize')
            ->assertForbidden();
    }

    public function test_bulk_recategorize_assigns_category_when_authorized(): void
    {
        $organization = Organization::factory()->create();
        $permission = Permission::query()->firstOrCreate(['key' => 'product.manage_category']);
        $role = Role::factory()->create(['organization_id' => $organization->id]);
        $role->permissions()->attach($permission->id);

        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->roles()->attach($role->id);

        $category = Category::factory()->create(['organization_id' => $organization->id]);
        $product = Product::factory()->create(['organization_id' => $organization->id, 'category_id' => null]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selected', [$product->id])
            ->set('bulkCategoryId', $category->id)
            ->call('bulkRecategorize');

        $this->assertSame($category->id, $product->fresh()->category_id);
    }
}
