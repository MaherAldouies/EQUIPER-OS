<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Content>
 */
class ContentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'product_id' => null,
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'generated_by' => 'ai',
            'brand_voice_id' => null,
            'status' => 'drafted',
        ];
    }
}
