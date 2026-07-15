<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'salla_product_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'salla_raw_payload' => ['id' => fake()->numberBetween(100000, 999999)],
            'salla_category_name' => fake()->words(2, true),
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'category_id' => null,
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 50, 5000),
            'brand_name' => fake()->company(),
            'is_agency_brand' => false,
            'lifecycle_state' => 'draft',
            'stock_quantity' => fake()->numberBetween(0, 100),
            'stock_status' => 'in_stock',
        ];
    }

    public function withCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => Category::factory(['organization_id' => $attributes['organization_id'] ?? Organization::factory()]),
        ]);
    }
}
