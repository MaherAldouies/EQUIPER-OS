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
}
