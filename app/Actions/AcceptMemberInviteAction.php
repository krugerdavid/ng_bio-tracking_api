<?php

namespace App\Actions;

use App\Models\MemberInvite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AcceptMemberInviteAction implements Action
{
    /**
     * @param  array{token: string, password: string}  $data
     */
    public function execute(...$args): User
    {
        [$data] = $args;

        $tokenHash = hash('sha256', $data['token']);

        /** @var MemberInvite|null $invite */
        $invite = MemberInvite::query()
            ->where('token', $tokenHash)
            ->first();

        if (! $invite || ! $invite->isValid()) {
            throw ValidationException::withMessages([
                'token' => ['La invitación no es válida o ya expiró. Pedile a un admin que te reenvíe el acceso.'],
            ]);
        }

        return DB::transaction(function () use ($invite, $data) {
            $user = User::query()->findOrFail($invite->user_id);
            $user->forceFill([
                'password' => Hash::make($data['password']),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $invite->update(['accepted_at' => now()]);

            MemberInvite::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $invite->id)
                ->whereNull('accepted_at')
                ->delete();

            return $user->fresh();
        });
    }
}
