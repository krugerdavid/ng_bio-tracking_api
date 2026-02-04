<?php

use App\Actions\LoginAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->action = app(LoginAction::class);
});

test('login returns user and token with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'valid@example.com',
        'password' => Hash::make('secret'),
    ]);

    $result = $this->action->execute('valid@example.com', 'secret');

    expect($result)->toHaveKeys(['user', 'access_token', 'token_type'])
        ->and($result['user']->id)->toBe($user->id)
        ->and($result['token_type'])->toBe('Bearer')
        ->and($result['access_token'])->not->toBeEmpty();
});

test('login throws validation exception for wrong password', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('correct'),
    ]);

    $this->action->execute('user@example.com', 'wrong');
})->throws(ValidationException::class);

test('login throws validation exception for unknown email', function () {
    $this->action->execute('unknown@example.com', 'any');
})->throws(ValidationException::class);

test('login validation exception has email key in messages', function () {
    User::factory()->create(['email' => 'u@example.com', 'password' => Hash::make('p')]);

    try {
        $this->action->execute('u@example.com', 'wrong');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('email')
            ->and($e->errors()['email'][0])->toContain('credenciales');
    }
});
