<?php

namespace Database\Factories;

use App\Models\Content;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentAsset>
 */
class ContentAssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'content_id' => Content::factory(),
            'channel' => 'instagram_caption',
            'body' => fake()->paragraph(),
            'channel_metadata' => [],
            'status' => 'generated',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => 'scheduled',
            'scheduled_for' => now()->addDay(),
        ]);
    }
}
