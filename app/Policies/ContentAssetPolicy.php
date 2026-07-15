<?php

namespace App\Policies;

use App\Models\ContentAsset;
use App\Models\User;

class ContentAssetPolicy
{
    public function schedule(User $user, ContentAsset $asset): bool
    {
        return $user->organization_id === $asset->organization_id
            && $user->hasPermission('content.edit')
            && $asset->status === 'approved';
    }

    public function confirmPublished(User $user, ContentAsset $asset): bool
    {
        return $user->organization_id === $asset->organization_id
            && $user->hasPermission('content.edit')
            && $asset->status === 'scheduled';
    }

    /**
     * Social Media Hub epic: direct publish via a platform API.
     * Broader than confirmPublished() — allows 'approved' (publish
     * immediately, skip scheduling) or 'scheduled' (publish early) —
     * matching ContentAsset::publishNow()'s own guard.
     */
    public function publish(User $user, ContentAsset $asset): bool
    {
        return $user->organization_id === $asset->organization_id
            && $user->hasPermission('social.manage')
            && in_array($asset->status, ['approved', 'scheduled'], true);
    }
}
