<?php

namespace App\Policies;

use App\Models\BrandVoice;
use App\Models\User;

class BrandVoicePolicy
{
    /**
     * Business Ontology rule (Brand Voice entity Permissions): "Write:
     * owner only." Note this checks the Role key directly rather than
     * a granular Permission, since Brand Voice is deliberately not
     * delegable in v1.0 — brand drift risk is too high to share this
     * write access broadly.
     */
    public function manage(User $user, ?BrandVoice $brandVoice = null): bool
    {
        if ($brandVoice && $user->organization_id !== $brandVoice->organization_id) {
            return false;
        }

        return $user->roles()->where('key', 'owner')->exists();
    }
}
