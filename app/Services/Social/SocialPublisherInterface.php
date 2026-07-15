<?php

namespace App\Services\Social;

use App\Models\ContentAsset;

/**
 * One implementation per connected platform (MetaPublisher,
 * TikTokPublisher, XPublisher...). Always called from a human-
 * triggered action (ContentAsset::publishNow()) — never from a
 * scheduler or automated trigger, per the Social Media Hub epic's
 * "human always has first and last say" requirement.
 */
interface SocialPublisherInterface
{
    /**
     * Publishes an already-approved ContentAsset to the platform and
     * returns the platform's own post/media ID for reference.
     */
    public function publish(ContentAsset $asset): string;
}
