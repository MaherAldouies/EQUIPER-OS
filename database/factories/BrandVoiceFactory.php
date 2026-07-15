<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BrandVoice>
 */
class BrandVoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(3),
            'tone_guidelines' => fake()->paragraph(),
            'vocabulary_notes' => fake()->sentence(),
            'things_to_avoid' => fake()->sentence(),
            'brand_facts' => fake()->paragraph(),
            'status' => 'draft',
        ];
    }
}
