<?php

use App\Actions\CreateUserAction;
use App\Enums\Role;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->action = app(CreateUserAction::class);
});

test('create user hashes password and returns user', function () {
    $data = [
        'name' => 'Test User',
        'email' => 'newuser@example.com',
        'password' => 'plainpassword',
        'role' => 'member',
    ];

    $user = $this->action->execute($data);

    expect($user->email)->toBe('newuser@example.com')
        ->and($user->role)->toBe(Role::Member)
        ->and($user->password)->not->toBe('plainpassword')
        ->and(hash_verify('plainpassword', $user->password))->toBeTrue();
});

function hash_verify(string $plain, string $hashed): bool
{
    return \Illuminate\Support\Facades\Hash::check($plain, $hashed);
}

test('create user with admin role succeeds', function () {
    $data = [
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
        'role' => 'admin',
    ];

    $user = $this->action->execute($data);

    expect($user->role)->toBe(Role::Admin);
});

test('create user throws validation exception for invalid role', function () {
    $data = [
        'name' => 'X',
        'email' => 'x@example.com',
        'password' => 'password',
        'role' => 'superadmin',
    ];

    $this->action->execute($data);
})->throws(ValidationException::class);

test('create user validation exception has role message', function () {
    $data = [
        'name' => 'X',
        'email' => 'x@example.com',
        'password' => 'password',
        'role' => 'invalid',
    ];

    try {
        $this->action->execute($data);
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('role');
    }
});
