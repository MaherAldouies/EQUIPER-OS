<?php

namespace App\Http\Controllers\Api;

use App\Models\Integration;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SocialMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * SocialInboxWebhookController — Social Media Hub epic. Inbound edge
 * for the unified reply inbox. Each connected platform gets its own
 * route below, all funneling into SocialMessage::recordInbound() so
 * the Inbox\Index Livewire component never needs to know which
 * platform a conversation came from.
 *
 * Phase 3 adds Meta (Instagram/Facebook) comments + DMs — same
 * verified-webhook shape as WhatsApp (Meta's own hub.challenge
 * handshake + X-Hub-Signature-256, since both are Graph API
 * webhooks), just a different `provider` value and payload parser.
 */
class SocialInboxWebhookController extends Controller
{
    /**
     * WhatsApp's one-time subscription handshake: Meta calls this with
     * hub.mode=subscribe and expects the hub.challenge value echoed
     * back verbatim if hub.verify_token matches what was configured
     * when setting up the webhook (either in the Integrations settings
     * page or WHATSAPP_VERIFY_TOKEN in .env).
     */
    public function verifyWhatsApp(Request $request): Response
    {
        $organization = Organization::query()->first();
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $expected = $organization ? Integration::config($organization->id, 'whatsapp', 'verify_token') : config('equiperos.whatsapp.verify_token');

        if ($mode === 'subscribe' && $expected && hash_equals((string) $expected, (string) $token)) {
            return response((string) $challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function handleWhatsApp(Request $request): Response
    {
        $organization = Organization::query()->firstOrFail();

        if (! $this->verifySignature($request, Integration::config($organization->id, 'whatsapp', 'app_secret'))) {
            Log::warning('WhatsApp webhook signature verification failed.');

            return response('Invalid signature', 401);
        }

        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    $fromName = collect($value['contacts'] ?? [])->first()['profile']['name'] ?? null;

                    SocialMessage::recordInbound([
                        'organization_id' => $organization->id,
                        'provider' => 'whatsapp',
                        'external_conversation_id' => $message['from'],
                        'external_message_id' => $message['id'] ?? null,
                        'from_name' => $fromName,
                        'body' => $message['text']['body'] ?? '['.($message['type'] ?? 'unsupported').' message]',
                        'received_at' => isset($message['timestamp']) ? now()->createFromTimestamp((int) $message['timestamp']) : now(),
                    ]);
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * Same hub.challenge handshake as WhatsApp (both are Graph API
     * webhooks under the same Meta App), but checked against the
     * Meta-specific verify token since Instagram/Facebook are
     * typically a separate subscription within the same app.
     */
    public function verifyMeta(Request $request): Response
    {
        $organization = Organization::query()->first();
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $expected = $organization ? Integration::config($organization->id, 'meta', 'verify_token') : config('equiperos.meta.verify_token');

        if ($mode === 'subscribe' && $expected && hash_equals((string) $expected, (string) $token)) {
            return response((string) $challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Handles both Instagram comment webhooks (`field: comments`) and
     * Instagram/Messenger DM webhooks (`messaging` array) — Meta sends
     * both event shapes to the same subscribed endpoint.
     */
    public function handleMeta(Request $request): Response
    {
        $organization = Organization::query()->firstOrFail();

        if (! $this->verifySignature($request, Integration::config($organization->id, 'meta', 'app_secret'))) {
            Log::warning('Meta webhook signature verification failed.');

            return response('Invalid signature', 401);
        }

        $provider = $request->input('object') === 'page' ? 'meta_facebook' : 'meta_instagram';

        foreach ($request->input('entry', []) as $entry) {
            // Comments (field: "comments")
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) !== 'comments') {
                    continue;
                }

                $value = $change['value'] ?? [];

                // The comment's own ID is both the conversation and
                // message ID — replying targets this specific comment
                // via POST /{comment-id}/replies, not a grouped thread.
                SocialMessage::recordInbound([
                    'organization_id' => $organization->id,
                    'provider' => $provider,
                    'message_type' => 'comment',
                    'external_conversation_id' => $value['id'],
                    'external_message_id' => $value['id'] ?? null,
                    'from_name' => $value['from']['username'] ?? $value['from']['id'] ?? null,
                    'body' => $value['text'] ?? '[unsupported comment]',
                    'received_at' => now(),
                ]);
            }

            // DMs (Messenger/Instagram Direct — "messaging" array)
            foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                if (! isset($messagingEvent['message']['text'])) {
                    continue;
                }

                SocialMessage::recordInbound([
                    'organization_id' => $organization->id,
                    'provider' => $provider,
                    'message_type' => 'dm',
                    'external_conversation_id' => $messagingEvent['sender']['id'],
                    'external_message_id' => $messagingEvent['message']['mid'] ?? null,
                    'from_name' => null,
                    'body' => $messagingEvent['message']['text'],
                    'received_at' => isset($messagingEvent['timestamp']) ? now()->createFromTimestampMs((int) $messagingEvent['timestamp']) : now(),
                ]);
            }
        }

        return response('OK', 200);
    }

    private function verifySignature(Request $request, ?string $secret): bool
    {
        if (! $secret) {
            return false;
        }

        $signatureHeader = (string) $request->header('X-Hub-Signature-256');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
