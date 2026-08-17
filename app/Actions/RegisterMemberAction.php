<?php

namespace App\Actions;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterMemberAction implements Action
{
    /**
     * @param  array{name: string, email: string, password: string, training_group?: string|null}  $data
     */
    public function execute(...$args): Member
    {
        [$data] = $args;

        return DB::transaction(function () use ($data) {
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
    }
}
