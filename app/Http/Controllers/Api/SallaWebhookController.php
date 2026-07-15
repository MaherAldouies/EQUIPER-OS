<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncSallaOrderJob;
use App\Jobs\SyncSallaProductJob;
use App\Models\Integration;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * SallaWebhookController — the inbound edge of the Anti-Corruption Layer.
 *
 * Dispatches queued Jobs rather than calling SallaSyncService
 * synchronously — Salla (like most webhook senders) may retry or
 * time out slow responses, so this endpoint must ack fast and let the
 * queue worker do the actual sync work.
 *
 * Confirmed against docs.salla.dev: payload is `{event, data}`;
 * `X-Salla-Security-Strategy` names the verification strategy
 * (`signature`/`token`/`none`) and `X-Salla-Signature` carries the
 * SHA-256 HMAC of the raw body when strategy is `signature`.
 */
class SallaWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // v1.0 scope: single-organization (Equiper only) — see Business
        // Ontology's Organization entity for the multi-tenant future.
        $organization = Organization::query()->firstOrFail();

        if (! $this->verifySignature($request, $organization->id)) {
            Log::warning('Salla webhook signature verification failed.');

            return response('Invalid signature', 401);
        }

        $eventType = $request->input('event');
        $payload = $request->input('data', []);

        match (true) {
            str_starts_with((string) $eventType, 'product.') => SyncSallaProductJob::dispatch($organization->id, $payload),
            str_starts_with((string) $eventType, 'order.') => SyncSallaOrderJob::dispatch($organization->id, $payload),
            default => Log::info('Unhandled Salla webhook event type', ['event' => $eventType]),
        };

        return response('OK', 200);
    }

    private function verifySignature(Request $request, string $organizationId): bool
    {
        $strategy = $request->header('X-Salla-Security-Strategy', 'signature');

        // Salla itself offers a "none" strategy for local/testing apps;
        // when the merchant app is configured that way there is nothing
        // to verify. Any other declared strategy still requires the
        // HMAC signature check below.
        if ($strategy === 'none') {
            return true;
        }

        $secret = Integration::config($organizationId, 'salla', 'webhook_secret');

        if (! $secret) {
            // Fails closed in any environment where the secret isn't configured.
            return false;
        }

        $signature = $request->header('X-Salla-Signature');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, (string) $signature);
    }
}
