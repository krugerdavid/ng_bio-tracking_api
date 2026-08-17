<?php

namespace App\Policies;

use App\Enums\BioimpedanceStatus;
use App\Models\Bioimpedance;
use App\Models\User;

class BioimpedancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Bioimpedance $bioimpedance): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member && $user->member->id === $bioimpedance->member_id;
    }

    /**
     * Admin/root crean para cualquier miembro; un alumno con ficha vinculada puede
     * cargar la suya propia (ownership y status se fuerzan en la Action, no acá).
     */
    public function create(User $user): bool
    {
        return $user->canAccessAllMembers() || $user->member !== null;
    }

    /**
     * Admin/root editan cualquier registro; un alumno solo el propio y solo
     * mientras siga pendiente de confirmación.
     */
    public function update(User $user, Bioimpedance $bioimpedance): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member
            && $user->member->id === $bioimpedance->member_id
            && $bioimpedance->status === BioimpedanceStatus::Pending;
    }

    public function delete(User $user, Bioimpedance $bioimpedance): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member
            && $user->member->id === $bioimpedance->member_id
            && $bioimpedance->status === BioimpedanceStatus::Pending;
    }
}
