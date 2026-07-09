<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\User;

class ApprovalPolicy
{
    public function decide(User $user, Approval $approval): bool
    {
        if ($user->organization_id !== $approval->organization_id) {
            return false;
        }

        if (! $user->hasPermission('content.approve') && ! $user->hasPermission('seo.approve')) {
            return false;
        }

        if ($approval->status !== 'pending') {
            return false;
        }

        // Business rule (Ontology, Approval entity): an expired Approval
        // must escalate, not silently accept a late decision.
        if ($approval->expires_at && $approval->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
