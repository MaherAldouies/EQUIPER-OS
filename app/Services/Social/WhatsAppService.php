<?php

namespace App\Services\Social;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * WhatsAppService — Social Media Hub epic, Phase 2. WhatsApp doesn't
 * fit SocialPublisherInterface (there's no "post" concept — only
 * conversational messages within a 24h session window), so this is a
 * standalone send/reply service consumed only by the unified inbox
 * (SocialMessage), never by ContentAsset::publishNow().
 *
 * Confirmed against developers.facebook.com: base URL
 * https://graph.facebook.com/v23.0/{phone_number_id}/messages, Bearer
 * token auth using a permanent System User access token issued
 * directly in the Meta App dashboard (no OAuth redirect flow needed
 * for a business's own number).
 */
class WhatsAppService
{
    public function sendMessage(string $organizationId, string $toPhoneNumber, string $body): string
    {
        $phoneNumberId = Integration::config($organizationId, 'whatsapp', 'phone_number_id');
        // Deliberately reads the credential's access_token column
        // directly (not Integration::config(), which only checks
        // settings/secrets/env — never the dedicated token columns).
        $token = Integration::query()
            ->where('organization_id', $organizationId)
            ->where('provider', 'whatsapp')
            ->first()
            ?->credential
            ?->access_token;

        if (! $phoneNumberId || ! $token) {
            throw new RuntimeException('WhatsApp is not configured — set it up on the Integrations settings page.');
        }

        $response = Http::withToken($token)
            ->baseUrl(Integration::config($organizationId, 'whatsapp', 'api_base_url', config('equiperos.whatsapp.api_base_url')))
            ->post("/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $toPhoneNumber,
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $body],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("WhatsApp send failed with status {$response->status()}: {$response->body()}");
        }

        return $response->json('messages.0.id', '');
    }
}
