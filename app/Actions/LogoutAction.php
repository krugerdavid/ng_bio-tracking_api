<?php

namespace App\Actions;

use App\Models\User;

class LogoutAction implements Action
{
    /**
     * @param User $user
     * @return bool
     */
    public function execute(...$args): bool
    {
        [$user] = $args;
        return $user->currentAccessToken()->delete();
    }
}
