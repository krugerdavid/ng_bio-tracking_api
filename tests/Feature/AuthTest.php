<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

test('it can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'user' => ['id', 'name', 'email', 'role'],
                'access_token',
                'token_type'
            ]
        ]);
});

test('it cannot login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJson(['status' => 'error']);
});

test('authenticated user can get me', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $response = $this->getJson('/api/me');
    $response->assertStatus(200)
        ->assertJson(['data' => ['id' => $user->id, 'email' => $user->email]]);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $response = $this->postJson('/api/logout');
    $response->assertStatus(200)->assertJson(['message' => 'Logout exitoso.']);
});
