<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Organization;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PollXMentionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_new_mentions_into_the_inbox(): void
    {
        $this->seed(EventCatalogSeeder::class);
        config(['equiperos.x.user_id' => '999', 'equiperos.x.api_base_url' => 'https://api.x.com/2']);

        $organization = Organization::factory()->create();
        $integration = Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'x',
            'status' => 'connected',
        ]);
        $integration->credential()->create([
            'access_token' => 'valid-token',
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            '*/users/999/mentions*' => Http::response([
                'data' => [
                    ['id' => 'T1', 'text' => '@equiper هل متوفر؟', 'author_id' => 'A1', 'created_at' => now()->toIso8601String()],
                ],
            ], 200),
        ]);

        $this->artisan('x:poll-mentions')->assertExitCode(0);

        $this->assertDatabaseHas('social_messages', [
            'organization_id' => $organization->id,
            'provider' => 'x',
            'external_conversation_id' => 'T1',
            'body' => '@equiper هل متوفر؟',
        ]);
    }
}
