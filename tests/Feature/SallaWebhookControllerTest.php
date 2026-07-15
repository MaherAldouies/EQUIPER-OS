<?php

namespace Tests\Feature;

use App\Jobs\SyncSallaOrderJob;
use App\Jobs\SyncSallaProductJob;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SallaWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['equiperos.salla.webhook_secret' => 'test-secret']);
        Organization::factory()->create();
        Queue::fake();
    }

    private function sign(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), 'test-secret');
    }

    public function test_rejects_request_with_invalid_signature(): void
    {
        $payload = ['event' => 'product.created', 'data' => ['id' => 1]];

        $response = $this->postJson('/api/webhooks/salla', $payload, [
            'X-Salla-Signature' => 'not-the-right-signature',
        ]);

        $response->assertStatus(401);
        Queue::assertNothingPushed();
    }

    public function test_rejects_request_when_webhook_secret_not_configured(): void
    {
        config(['equiperos.salla.webhook_secret' => null]);
        $payload = ['event' => 'product.created', 'data' => ['id' => 1]];

        $response = $this->postJson('/api/webhooks/salla', $payload, [
            'X-Salla-Signature' => $this->sign($payload),
        ]);

        $response->assertStatus(401);
    }

    public function test_accepts_valid_signature_and_dispatches_product_sync_job(): void
    {
        $payload = ['event' => 'product.quantity.low', 'data' => ['id' => 555, 'name' => 'Test']];

        $response = $this->postJson('/api/webhooks/salla', $payload, [
            'X-Salla-Signature' => $this->sign($payload),
        ]);

        $response->assertStatus(200);
        Queue::assertPushed(SyncSallaProductJob::class);
    }

    public function test_dispatches_order_sync_job_for_order_events(): void
    {
        $payload = ['event' => 'order.status.updated', 'data' => ['id' => 999]];

        $response = $this->postJson('/api/webhooks/salla', $payload, [
            'X-Salla-Signature' => $this->sign($payload),
        ]);

        $response->assertStatus(200);
        Queue::assertPushed(SyncSallaOrderJob::class);
    }

    public function test_none_security_strategy_bypasses_signature_check(): void
    {
        $payload = ['event' => 'product.created', 'data' => ['id' => 1]];

        $response = $this->postJson('/api/webhooks/salla', $payload, [
            'X-Salla-Security-Strategy' => 'none',
        ]);

        $response->assertStatus(200);
    }
}
