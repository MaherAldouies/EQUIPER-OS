<?php

namespace App\Services\Social;

use App\Models\ContentAsset;
use RuntimeException;

/**
 * XPublisher — Social Media Hub epic, Phase 5.
 */
class XPublisher implements SocialPublisherInterface
{
    public function publish(ContentAsset $asset): string
    {
        if ($asset->channel !== 'x_post') {
            throw new RuntimeException("XPublisher cannot publish channel '{$asset->channel}'.");
        }

        return (new XApiClient($asset->organization_id))->postTweet($asset->body);
    }
}
