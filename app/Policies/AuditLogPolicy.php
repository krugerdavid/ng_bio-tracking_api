<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Only root and admin can view audit logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessAllMembers();
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->canAccessAllMembers();
    }
}
