<?php

namespace Tests\Unit\Services;

use App\Models\Organization;
use App\Services\Salla\SallaSyncService;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SallaSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
    }

    public function test_sync_product_reads_category_name_from_categories_array(): void
    {
        $organization = Organization::factory()->create();
        $service = new SallaSyncService($organization->id);

        // Real Salla Product Details shape: `categories` is an array of
        // objects, not a singular `category` object.
        $product = $service->syncProduct([
            'id' => 123456,
            'name' => 'ثلاجة تجارية',
            'sku' => 'SKU-1',
            'price' => ['amount' => 1500, 'currency' => 'SAR'],
            'quantity' => 3,
            'categories' => [
                ['id' => 1, 'name' => 'ثلاجات وتبريد'],
                ['id' => 2, 'name' => 'أجهزة تجارية'],
            ],
        ]);

        $this->assertSame('ثلاجات وتبريد', $product->salla_category_name);
        $this->assertSame('low_stock', $product->stock_status);
    }

    public function test_sync_order_reads_status_slug_from_status_object(): void
    {
        $organization = Organization::factory()->create();
        $service = new SallaSyncService($organization->id);

        // Real Salla Order Details shape: `status` is an object
        // {name,color,slug}, not a plain string.
        $order = $service->syncOrder([
            'id' => 999,
            'status' => ['name' => 'Completed', 'color' => '#00C853', 'slug' => 'completed'],
            'amounts' => ['total' => ['amount' => 302.5, 'currency' => 'SAR']],
            'customer' => ['id' => 42],
            'created_at' => now()->toIso8601String(),
            'items' => [],
        ]);

        $this->assertSame('completed', $order->status);
        $this->assertEquals(302.5, (float) $order->total_amount);
        $this->assertEquals(42, $order->customer_reference);
    }

    public function test_sync_order_falls_back_to_placed_for_unrecognized_status_slug(): void
    {
        $organization = Organization::factory()->create();
        $service = new SallaSyncService($organization->id);

        $order = $service->syncOrder([
            'id' => 1000,
            'status' => ['name' => 'Custom Merchant Status', 'slug' => 'some_custom_slug'],
            'amounts' => ['total' => ['amount' => 100, 'currency' => 'SAR']],
            'items' => [],
        ]);

        $this->assertSame('placed', $order->status);
    }
}
