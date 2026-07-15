<?php

namespace Tests\Feature;

use App\Models\Organization;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialInboxMetaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
        config(['equiperos.meta.verify_token' => 'meta-verify', 'equiperos.meta.app_secret' => 'meta-secret']);
        Organization::factory()->create();
    }

    private function sign(array $payload): string
    {
        return 'sha256='.hash_hmac('sha256', json_encode($payload), 'meta-secret');
    }

    public function test_verification_handshake(): void
    {
        $response = $this->get('/api/webhooks/meta?hub_mode=subscribe&hub_verify_token=meta-verify&hub_challenge=999');

        $response->assertStatus(200);
        $response->assertSee('999');
    }

    public function test_records_inbound_instagram_comment(): void
    {
        $payload = [
            'object' => 'instagram',
            'entry' => [[
                'id' => '17841400000000000',
                'changes' => [[
                    'field' => 'comments',
                    'value' => [
                        'id' => 'COMMENT1',
                        'text' => 'هل متوفر بمقاس أكبر؟',
                        'from' => ['id' => '999', 'username' => 'sara_k'],
                        'media' => ['id' => 'MEDIA1'],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/webhooks/meta', $payload, [
            'X-Hub-Signature-256' => $this->sign($payload),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('social_messages', [
            'provider' => 'meta_instagram',
            'message_type' => 'comment',
            // The comment's own ID, not the media ID — replying targets
            // this specific comment via POST /{comment-id}/replies.
            'external_conversation_id' => 'COMMENT1',
            'from_name' => 'sara_k',
            'body' => 'هل متوفر بمقاس أكبر؟',
        ]);
    }

    public function test_records_inbound_dm(): void
    {
        $payload = [
            'object' => 'instagram',
            'entry' => [[
                'id' => '17841400000000000',
                'messaging' => [[
                    'sender' => ['id' => '555'],
                    'recipient' => ['id' => '17841400000000000'],
                    'timestamp' => now()->timestamp * 1000,
                    'message' => ['mid' => 'MID1', 'text' => 'What is the price?'],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/webhooks/meta', $payload, [
            'X-Hub-Signature-256' => $this->sign($payload),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('social_messages', [
            'provider' => 'meta_instagram',
            'message_type' => 'dm',
            'external_conversation_id' => '555',
            'body' => 'What is the price?',
        ]);
    }
}
