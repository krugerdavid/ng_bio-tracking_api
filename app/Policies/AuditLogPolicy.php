<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Only root can view audit logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->isRoot();
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->isRoot();
    }
}
