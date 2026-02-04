<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated user cannot list users', function () {
    $response = $this->getJson('/api/users');
    $response->assertStatus(401)->assertJson(['message' => 'No autenticado.']);
});

test('unauthenticated user cannot create user', function () {
    $response = $this->postJson('/api/users', [
        'email' => 'new@example.com',
        'password' => 'password123',
        'role' => 'member',
    ]);
    $response->assertStatus(401);
});

test('member role cannot list users', function () {
    Sanctum::actingAs(User::factory()->member()->create());
    $response = $this->getJson('/api/users');
    $response->assertStatus(403);
});

test('admin can list users', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    User::factory()->count(2)->create();
    $response = $this->getJson('/api/users');
    $response->assertStatus(200)
        ->assertJsonStructure(['status', 'data'])
        ->assertJson(['status' => 'success']);
});

test('root can list users', function () {
    Sanctum::actingAs(User::factory()->root()->create());
    $response = $this->getJson('/api/users');
    $response->assertStatus(200);
});

test('admin cannot create admin user', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->postJson('/api/users', [
        'email' => 'admin2@example.com',
        'password' => 'password123',
        'role' => 'admin',
    ]);
    $response->assertStatus(403)
        ->assertJson(['message' => 'Solo root puede crear usuarios administrador.']);
});

test('root can create member user', function () {
    Sanctum::actingAs(User::factory()->root()->create());
    $response = $this->postJson('/api/users', [
        'email' => 'newmember@example.com',
        'password' => 'password123',
        'role' => 'member',
    ]);
    $response->assertStatus(201)
        ->assertJson(['status' => 'success', 'data' => ['email' => 'newmember@example.com']]);
    $this->assertDatabaseHas('users', ['email' => 'newmember@example.com']);
});

test('create user returns 422 when validation fails', function () {
    Sanctum::actingAs(User::factory()->root()->create());
    $response = $this->postJson('/api/users', [
        'email' => 'invalid-email',
        'password' => 'short',
        'role' => 'invalid',
    ]);
    $response->assertStatus(422)->assertJsonValidationErrors(['email', 'password', 'role']);
});

test('show user returns 404 for non-existent id', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/users/99999');
    $response->assertStatus(404)->assertJson(['message' => 'Usuario no encontrado.']);
});

test('admin can show existing user', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($admin);
    $response = $this->getJson('/api/users/' . $other->id);
    $response->assertStatus(200)->assertJson(['data' => ['id' => $other->id]]);
});

test('update user returns 404 for non-existent id', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->putJson('/api/users/99999', ['name' => 'Updated']);
    $response->assertStatus(404);
});

test('admin can update user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Old Name']);
    Sanctum::actingAs($admin);
    $response = $this->putJson('/api/users/' . $user->id, ['name' => 'New Name']);
    $response->assertStatus(200);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
});

test('destroy user returns 404 for non-existent id', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->deleteJson('/api/users/99999');
    $response->assertStatus(404);
});

test('admin can delete user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    Sanctum::actingAs($admin);
    $response = $this->deleteJson('/api/users/' . $user->id);
    $response->assertStatus(200)->assertJson(['message' => 'Usuario eliminado.']);
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
