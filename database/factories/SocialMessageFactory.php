<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialMessage>
 */
class SocialMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'provider' => 'whatsapp',
            'message_type' => 'dm',
            'external_conversation_id' => fake()->unique()->numerify('9665########'),
            'external_message_id' => 'wamid.'.fake()->uuid(),
            'direction' => 'inbound',
            'from_name' => fake()->name(),
            'body' => fake()->sentence(),
            'status' => 'unread',
            'received_at' => now(),
        ];
    }
}
