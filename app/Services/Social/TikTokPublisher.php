<?php

namespace App\Services\Social;

use App\Models\ContentAsset;
use App\Models\Integration;
use App\Models\IntegrationCredential;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * TikTokPublisher — Social Media Hub epic, Phase 4. Publish-only: the
 * TikTok Content Posting API has no public endpoint to read or reply
 * to comments at all (confirmed against developers.tiktok.com — the
 * only comment-related control is `disable_comment` at post time).
 * This is a hard platform limitation, not something this class can
 * work around — TikTok conversations never appear in the unified
 * Inbox\Index component.
 *
 * Uses the PULL_FROM_URL source type (simplest: TikTok fetches the
 * video from a URL EQUIPER OS already hosts, no chunked upload needed)
 * against POST https://open.tiktokapis.com/v2/post/publish/video/init/.
 * Publishing is async on TikTok's side — this call only queues it and
 * returns a publish_id, it does not guarantee the video is live yet.
 *
 * Token resolution mirrors XApiClient: reads the OAuth credential
 * (access_token/refresh_token columns on IntegrationCredential — set
 * either via "Connect with TikTok" or a manually pasted access token)
 * and retries once on a live 401 by refreshing.
 */
class TikTokPublisher implements SocialPublisherInterface
{
    public function publish(ContentAsset $asset): string
    {
        if ($asset->channel !== 'tiktok_video') {
            throw new RuntimeException("TikTokPublisher cannot publish channel '{$asset->channel}'.");
        }

        $videoUrl = $asset->channel_metadata['video_url'] ?? null;

        if (! $videoUrl) {
            throw new RuntimeException(
                "ContentAsset {$asset->id} has no channel_metadata.video_url — TikTok requires a hosted video file."
            );
        }

        $orgId = $asset->organization_id;
        $baseUrl = Integration::config($orgId, 'tiktok', 'api_base_url', config('equiperos.tiktok.api_base_url'));

        $payload = [
            'post_info' => [
                'title' => $asset->body,
                'privacy_level' => Integration::config($orgId, 'tiktok', 'privacy_level', config('equiperos.tiktok.privacy_level', 'PUBLIC_TO_EVERYONE')),
                'disable_comment' => false,
            ],
            'source_info' => [
                'source' => 'PULL_FROM_URL',
                'video_url' => $videoUrl,
            ],
        ];

        $send = fn (string $token) => Http::withToken($token)
            ->baseUrl($baseUrl)
            ->post('/post/publish/video/init/', $payload);

        $response = $send($this->accessToken($orgId));

        if ($response->status() === 401) {
            $response = $send($this->refreshAccessToken($orgId, $this->credential($orgId)));
        }

        if (! $response->successful()) {
            throw new RuntimeException("TikTok publish failed with status {$response->status()}: {$response->body()}");
        }

        return $response->json('data.publish_id', '');
    }

    private function accessToken(string $orgId): string
    {
        $credential = $this->credential($orgId);

        if ($credential->isExpired() && $credential->refresh_token) {
            return $this->refreshAccessToken($orgId, $credential);
        }

        if (! $credential->access_token) {
            throw new RuntimeException('TikTok is not configured — set it up on the Integrations settings page.');
        }

        return (string) $credential->access_token;
    }

    private function refreshAccessToken(string $orgId, IntegrationCredential $credential): string
    {
        if (! $credential->refresh_token) {
            throw new RuntimeException('TikTok access token expired and no refresh token is stored — reconnect on the Integrations settings page.');
        }

        $tokenUrl = Integration::config($orgId, 'tiktok', 'token_url', config('equiperos.tiktok.token_url'));

        $response = Http::asForm()->post($tokenUrl, [
            'client_key' => Integration::config($orgId, 'tiktok', 'client_key'),
            'client_secret' => Integration::config($orgId, 'tiktok', 'client_secret'),
            'refresh_token' => $credential->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("TikTok token refresh failed with status {$response->status()}: {$response->body()}");
        }

        $body = $response->json();

        $credential->forceFill([
            'access_token' => $body['access_token'],
            'refresh_token' => $body['refresh_token'] ?? $credential->refresh_token,
            'expires_at' => now()->addSeconds((int) ($body['expires_in'] ?? 86400)),
        ])->save();

        return (string) $credential->access_token;
    }

    private function credential(string $orgId): IntegrationCredential
    {
        $integration = Integration::query()->firstOrCreate(
            ['organization_id' => $orgId, 'provider' => 'tiktok'],
            ['status' => 'configuring']
        );

        $credential = $integration->credential;

        if (! $credential) {
            throw new RuntimeException('TikTok is not configured — set it up on the Integrations settings page.');
        }

        return $credential;
    }
}
