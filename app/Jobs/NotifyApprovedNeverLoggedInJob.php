<?php

namespace App\Jobs;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Mail\RegistrationApprovedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyApprovedNeverLoggedInJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Miembros activos (aprobados) que todavía no iniciaron sesión.
     * Excluye invitaciones pendientes: esos usuarios no eligieron contraseña.
     *
     * @return Collection<int, User>
     */
    public static function recipients(): Collection
    {
        return User::query()
            ->where('role', Role::Member)
            ->where('status', UserStatus::Active)
            ->whereNull('last_login_at')
            ->whereNotNull('email')
            ->whereHas('member')
            ->whereDoesntHave('member.invites', fn ($q) => $q->whereNull('accepted_at'))
            ->with('member')
            ->orderBy('id')
            ->get();
    }

    public function handle(): int
    {
        $sent = 0;

        foreach (self::recipients() as $user) {
            $member = $user->member;
            if (! $member) {
                continue;
            }

            Mail::to($user->email)->send(new RegistrationApprovedMail($member));
            $sent++;
        }

        return $sent;
    }
}
