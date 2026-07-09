<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function manage(User $user, Campaign $campaign): bool
    {
        return $user->organization_id === $campaign->organization_id
            && $user->hasPermission('campaign.manage');
    }

    public function complete(User $user, Campaign $campaign): bool
    {
        return $this->manage($user, $campaign)
            && $campaign->status !== 'completed'
            && ! $campaign->contentAssets()->where('status', 'scheduled')->exists();
    }
}
