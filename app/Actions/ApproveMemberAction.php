<?php

namespace App\Actions;

use App\Enums\UserStatus;
use App\Mail\RegistrationApprovedMail;
use App\Models\Member;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ApproveMemberAction implements Action
{
    public function execute(...$args): Member
    {
        [$member] = $args;

        $user = $member->user;

        if (! $user) {
            throw ValidationException::withMessages([
                'member' => ['Este miembro no tiene una cuenta de usuario vinculada.'],
            ]);
        }

        if ($user->status !== UserStatus::Pending) {
            throw ValidationException::withMessages([
                'member' => ['Este registro no está pendiente de aprobación.'],
            ]);
        }

        $user->update(['status' => UserStatus::Active->value]);

        $member = $member->fresh(['user']);
        $email = $member->email ?: $user->email;
        if ($email) {
            Mail::to($email)->send(new RegistrationApprovedMail($member));
        }

        return $member;
    }
}
