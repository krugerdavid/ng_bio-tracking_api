<?php

namespace App\Actions;

use App\Enums\Role;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateUserAction implements Action
{
    public function __construct(private UserRepository $repository) {}

    /**
     * @param array{name: string, email: string, password: string, role: string} $data
     * @return \App\Models\User
     */
    public function execute(...$args): \App\Models\User
    {
        [$data] = $args;

        if (! in_array($data['role'], Role::assignableValues(), true)) {
            throw ValidationException::withMessages([
                'role' => [__('Solo se pueden crear usuarios con rol :roles.', ['roles' => implode(' o ', Role::assignableValues())])],
            ]);
        }

        $data['password'] = Hash::make($data['password']);

        return $this->repository->create($data);
    }
}
