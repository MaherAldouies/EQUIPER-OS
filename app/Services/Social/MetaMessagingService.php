<?php

namespace App\Services\Social;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * MetaMessagingService — Social Media Hub epic, Phase 3/5. Replies to
 * an Instagram/Facebook comment or DM from the unified inbox. Two
 * distinct Graph API endpoints depending on message_type: a comment
 * reply targets the comment's own ID; a DM reply targets the page and
 * the sender's PSID.
 */
class MetaMessagingService
{
    public function replyToComment(string $organizationId, string $commentId, string $text): string
    {
        $response = Http::withToken($this->token($organizationId))
            ->baseUrl(Integration::config($organizationId, 'meta', 'api_base_url', config('equiperos.meta.api_base_url')))
            ->post("/{$commentId}/replies", ['message' => $text]);

        if (! $response->successful()) {
            throw new RuntimeException("Meta comment reply failed with status {$response->status()}: {$response->body()}");
        }

        return $response->json('id', '');
    }

    public function replyToDm(string $organizationId, string $recipientPsid, string $text): string
    {
        $pageId = Integration::config($organizationId, 'meta', 'page_id');

        $response = Http::withToken($this->token($organizationId))
            ->baseUrl(Integration::config($organizationId, 'meta', 'api_base_url', config('equiperos.meta.api_base_url')))
            ->post("/{$pageId}/messages", [
                'recipient' => ['id' => $recipientPsid],
                'message' => ['text' => $text],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("Meta DM reply failed with status {$response->status()}: {$response->body()}");
        }

        return $response->json('message_id', '');
    }

    private function token(string $organizationId): string
    {
        // Deliberately reads the credential's access_token column
        // directly (not Integration::config(), which only checks
        // settings/secrets/env — never the dedicated token columns).
        $token = Integration::query()
            ->where('organization_id', $organizationId)
            ->where('provider', 'meta')
            ->first()
            ?->credential
            ?->access_token;

        if (! $token) {
            throw new RuntimeException('Meta is not configured — set it up on the Integrations settings page.');
        }

        return $token;
    }
}
