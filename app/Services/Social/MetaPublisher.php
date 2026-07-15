<?php

namespace App\Services\Social;

use App\Models\ContentAsset;
use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * MetaPublisher — Social Media Hub epic, Phase 3. Publishes to
 * Instagram (channel 'instagram_caption') or a Facebook Page (channel
 * 'facebook_post') via the Graph API. Confirmed against
 * developers.facebook.com: base URL https://graph.facebook.com/v23.0,
 * Instagram publishing is a two-step container->publish call, Facebook
 * Page posting is a single call. Requires the connected asset's
 * channel_metadata to carry an `image_url` for Instagram (a caption
 * alone cannot be published — Instagram has no text-only post type).
 *
 * NOTE: going live against real (non-test) accounts requires Meta App
 * Review approval for instagram_content_publish/pages_manage_posts —
 * an external, asynchronous process this class cannot shortcut.
 */
class MetaPublisher implements SocialPublisherInterface
{
    public function publish(ContentAsset $asset): string
    {
        return match ($asset->channel) {
            'instagram_caption' => $this->publishToInstagram($asset),
            'facebook_post' => $this->publishToFacebook($asset),
            default => throw new RuntimeException("MetaPublisher cannot publish channel '{$asset->channel}'."),
        };
    }

    private function publishToInstagram(ContentAsset $asset): string
    {
        $orgId = $asset->organization_id;
        $igUserId = Integration::config($orgId, 'meta', 'ig_user_id');
        $token = $this->token($orgId);
        $baseUrl = Integration::config($orgId, 'meta', 'api_base_url', config('equiperos.meta.api_base_url'));
        $imageUrl = $asset->channel_metadata['image_url'] ?? null;

        if (! $imageUrl) {
            throw new RuntimeException(
                "ContentAsset {$asset->id} has no channel_metadata.image_url — Instagram has no ".
                'text-only post type, a media URL is required before publishing.'
            );
        }

        // Step 1: create the media container.
        $container = Http::withToken($token)
            ->baseUrl($baseUrl)
            ->post("/{$igUserId}/media", [
                'image_url' => $imageUrl,
                'caption' => $asset->body,
            ]);

        if (! $container->successful()) {
            throw new RuntimeException("Instagram media container creation failed with status {$container->status()}: {$container->body()}");
        }

        $creationId = $container->json('id');

        // Step 2: publish the container.
        $publish = Http::withToken($token)
            ->baseUrl($baseUrl)
            ->post("/{$igUserId}/media_publish", ['creation_id' => $creationId]);

        if (! $publish->successful()) {
            throw new RuntimeException("Instagram media publish failed with status {$publish->status()}: {$publish->body()}");
        }

        return $publish->json('id');
    }

    private function publishToFacebook(ContentAsset $asset): string
    {
        $orgId = $asset->organization_id;
        $pageId = Integration::config($orgId, 'meta', 'page_id');
        $token = $this->token($orgId);
        $imageUrl = $asset->channel_metadata['image_url'] ?? null;

        $response = Http::withToken($token)
            ->baseUrl(Integration::config($orgId, 'meta', 'api_base_url', config('equiperos.meta.api_base_url')))
            ->post($imageUrl ? "/{$pageId}/photos" : "/{$pageId}/feed", array_filter([
                'message' => $asset->body,
                'url' => $imageUrl,
            ]));

        if (! $response->successful()) {
            throw new RuntimeException("Facebook post failed with status {$response->status()}: {$response->body()}");
        }

        return $response->json('post_id') ?? $response->json('id');
    }

    private function token(string $organizationId): string
    {
        $token = Integration::config($organizationId, 'meta', 'access_token');

        if (! $token) {
            throw new RuntimeException('Meta is not configured — set it up on the Integrations settings page.');
        }

        return $token;
    }
}
