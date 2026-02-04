<?php

use App\Actions\LogoutAction;
use App\Models\User;

beforeEach(function () {
    $this->action = app(LogoutAction::class);
});

test('logout deletes current access token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test');
    expect($user->tokens()->count())->toBe(1);

    // Simular que currentAccessToken() devuelve este token (como en una petición autenticada)
    $user->withAccessToken($token->accessToken);
    $result = $this->action->execute($user);

    expect($result)->toBeTrue();
    expect($user->tokens()->count())->toBe(0);
});
