<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function invite(User $user): bool
    {
        return $user->hasPermission('team.manage');
    }

    public function viewTeam(User $user): bool
    {
        return $user->hasPermission('team.manage');
    }
}
