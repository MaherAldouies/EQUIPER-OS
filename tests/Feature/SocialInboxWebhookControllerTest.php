<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SocialMessage;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialInboxWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
        config(['equiperos.whatsapp.verify_token' => 'test-verify-token']);
        config(['equiperos.whatsapp.app_secret' => 'test-app-secret']);
        Organization::factory()->create();
    }

    public function test_verification_handshake_echoes_challenge_when_token_matches(): void
    {
        $response = $this->get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=test-verify-token&hub_challenge=12345');

        $response->assertStatus(200);
        $response->assertSee('12345');
    }

    public function test_verification_handshake_rejects_wrong_token(): void
    {
        $response = $this->get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=12345');

        $response->assertStatus(403);
    }

    private function sign(array $payload): string
    {
        return 'sha256='.hash_hmac('sha256', json_encode($payload), 'test-app-secret');
    }

    public function test_rejects_incoming_message_with_invalid_signature(): void
    {
        $payload = ['entry' => []];

        $response = $this->postJson('/api/webhooks/whatsapp', $payload, [
            'X-Hub-Signature-256' => 'sha256=invalid',
        ]);

        $response->assertStatus(401);
    }

    public function test_accepts_valid_signature_and_records_inbound_message(): void
    {
        $payload = [
            'entry' => [[
                'id' => '102290129340398',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'contacts' => [['profile' => ['name' => 'Sheena Nelson'], 'wa_id' => '16505551234']],
                        'messages' => [[
                            'from' => '16505551234',
                            'id' => 'wamid.ABC123',
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Does it come in another color?'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/webhooks/whatsapp', $payload, [
            'X-Hub-Signature-256' => $this->sign($payload),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('social_messages', [
            'provider' => 'whatsapp',
            'external_conversation_id' => '16505551234',
            'external_message_id' => 'wamid.ABC123',
            'from_name' => 'Sheena Nelson',
            'body' => 'Does it come in another color?',
            'direction' => 'inbound',
            'status' => 'unread',
        ]);
    }
}
