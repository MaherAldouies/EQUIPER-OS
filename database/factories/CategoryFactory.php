<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'organization_id' => Organization::factory(),
            'parent_id' => null,
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'status' => 'active',
        ];
    }
}
