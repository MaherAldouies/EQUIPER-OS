<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncSallaOrderJob;
use App\Jobs\SyncSallaProductJob;
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
 * TODO (Sprint 0 spike): confirm actual Salla webhook signature scheme
 * and event type names before relying on this in production. The
 * signature verification below is a placeholder structure, not a
 * verified implementation.
 */
class SallaWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        if (! $this->verifySignature($request)) {
            Log::warning('Salla webhook signature verification failed.');

            return response('Invalid signature', 401);
        }

        // v1.0 scope: single-organization (Equiper only) — see Business
        // Ontology's Organization entity for the multi-tenant future.
        $organization = Organization::query()->firstOrFail();

        $eventType = $request->input('event'); // e.g. "product.updated" — placeholder pending spike
        $payload = $request->input('data', []);

        match (true) {
            str_starts_with((string) $eventType, 'product.') => SyncSallaProductJob::dispatch($organization->id, $payload),
            str_starts_with((string) $eventType, 'order.') => SyncSallaOrderJob::dispatch($organization->id, $payload),
            default => Log::info('Unhandled Salla webhook event type', ['event' => $eventType]),
        };

        return response('OK', 200);
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('equiperos.salla.webhook_secret');

        if (! $secret) {
            // Fails closed in any environment where the secret isn't configured.
            return false;
        }

        $signature = $request->header('X-Salla-Signature'); // placeholder header name pending spike
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, (string) $signature);
    }
}
