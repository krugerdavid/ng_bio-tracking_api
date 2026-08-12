<?php

namespace App\Actions;

use App\Enums\Role;
use App\Mail\MemberInviteMail;
use App\Models\Member;
use App\Models\MemberInvite;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteMemberAction implements Action
{
    public const EXPIRES_HOURS = 72;

    public function __construct(private UserRepository $users) {}

    /**
     * @param  array{email?: string|null}  $data
     * @return array{member: Member, email: string, resent: bool}
     */
    public function execute(...$args): array
    {
        /** @var Member $member */
        [$member, $data] = $args;

        $email = $this->resolveEmail($member, $data['email'] ?? null);

        return DB::transaction(function () use ($member, $email) {
            $resent = (bool) $member->user_id;

            if ($member->email !== $email) {
                $member->update(['email' => $email]);
            }

            $user = $this->resolveOrCreateUser($member, $email);

            if ((int) $member->user_id !== (int) $user->id) {
                $member->update(['user_id' => $user->id]);
                $member->refresh();
            }

            MemberInvite::query()
                ->where('member_id', $member->id)
                ->whereNull('accepted_at')
                ->delete();

            $plainToken = Str::random(64);

            MemberInvite::query()->create([
                'member_id' => $member->id,
                'user_id' => $user->id,
                'email' => $email,
                'token' => hash('sha256', $plainToken),
                'expires_at' => now()->addHours(self::EXPIRES_HOURS),
            ]);

            $acceptUrl = rtrim((string) config('app.frontend_url'), '/')
                .'/accept-invite?token='.urlencode($plainToken);

            Mail::to($email)->send(new MemberInviteMail(
                $member->fresh(),
                $acceptUrl,
                self::EXPIRES_HOURS,
            ));

            return [
                'member' => $member->fresh(),
                'email' => $email,
                'resent' => $resent,
            ];
        });
    }

    private function resolveEmail(Member $member, ?string $provided): string
    {
        $email = $provided !== null && $provided !== ''
            ? strtolower(trim($provided))
            : ($member->email ? strtolower(trim($member->email)) : null);

        if (! $email) {
            throw ValidationException::withMessages([
                'email' => ['El email es obligatorio para invitar (el miembro no tiene correo cargado).'],
            ]);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => ['El email no es válido.'],
            ]);
        }

        $emailTakenByOtherMember = Member::query()
            ->where('email', $email)
            ->where('id', '!=', $member->id)
            ->exists();

        if ($emailTakenByOtherMember) {
            throw ValidationException::withMessages([
                'email' => ['Ese email ya está asignado a otro miembro.'],
            ]);
        }

        return $email;
    }

    private function resolveOrCreateUser(Member $member, string $email): User
    {
        if ($member->user_id) {
            $user = User::query()->find($member->user_id);
            if (! $user) {
                throw ValidationException::withMessages([
                    'email' => ['El usuario vinculado al miembro no existe.'],
                ]);
            }

            if (strcasecmp($user->email, $email) !== 0) {
                $emailOwned = User::query()
                    ->where('email', $email)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($emailOwned) {
                    throw ValidationException::withMessages([
                        'email' => ['Ese email ya pertenece a otra cuenta de usuario.'],
                    ]);
                }

                $user->update(['email' => $email]);
            }

            return $user->fresh();
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing) {
            if ($existing->member && $existing->member->id !== $member->id) {
                throw ValidationException::withMessages([
                    'email' => ['Ese email ya tiene una cuenta vinculada a otro miembro.'],
                ]);
            }

            if (! $existing->isMember()) {
                throw ValidationException::withMessages([
                    'email' => ['Ese email ya está registrado con otro rol (admin/root).'],
                ]);
            }

            return $existing;
        }

        return $this->users->create([
            'name' => $member->name,
            'email' => $email,
            'password' => Str::password(32),
            'role' => Role::Member->value,
        ]);
    }
}
