<?php

namespace App\Services\Social;

use App\Models\ContentAsset;
use App\Models\Integration;
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
        $token = Integration::config($orgId, 'tiktok', 'access_token');

        if (! $token) {
            throw new RuntimeException('TikTok is not configured — set it up on the Integrations settings page.');
        }

        $response = Http::withToken($token)
            ->baseUrl(Integration::config($orgId, 'tiktok', 'api_base_url', config('equiperos.tiktok.api_base_url')))
            ->post('/post/publish/video/init/', [
                'post_info' => [
                    'title' => $asset->body,
                    'privacy_level' => Integration::config($orgId, 'tiktok', 'privacy_level', config('equiperos.tiktok.privacy_level', 'PUBLIC_TO_EVERYONE')),
                    'disable_comment' => false,
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'video_url' => $videoUrl,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("TikTok publish failed with status {$response->status()}: {$response->body()}");
        }

        return $response->json('data.publish_id', '');
    }
}
