<?php

namespace App\Actions;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Mail\NewRegistrationAdminMail;
use App\Mail\WelcomeMemberMail;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RegisterMemberAction implements Action
{
    /**
     * @param  array{name: string, email: string, password: string, training_group?: string|null}  $data
     */
    public function execute(...$args): Member
    {
        [$data] = $args;

        $member = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => Role::Member->value,
                'status' => UserStatus::Pending->value,
            ]);

            return Member::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'training_group' => $data['training_group'] ?? null,
                'user_id' => $user->id,
            ]);
        });

        $this->notify($member);

        return $member;
    }

    private function notify(Member $member): void
    {
        Mail::to($member->email)->send(new WelcomeMemberMail($member));

        $pendingUrl = rtrim((string) config('app.frontend_url'), '/').'/members?status=pending';

        $adminEmails = User::query()
            ->whereIn('role', [Role::Admin->value, Role::Root->value])
            ->pluck('email');

        foreach ($adminEmails as $adminEmail) {
            Mail::to($adminEmail)->send(new NewRegistrationAdminMail($member, $pendingUrl));
        }
    }
}
