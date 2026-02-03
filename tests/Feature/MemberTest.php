<?php

use App\Models\Member;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('authenticated admin can list members', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    Member::factory()->count(3)->create();

    $response = $this->getJson('/api/members');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'data' => [
                    '*' => ['id', 'name', 'email', 'document_number']
                ]
            ]
        ]);
});

test('authenticated admin can create member', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->postJson('/api/members', [
        'name' => 'New Member',
        'document_number' => '1234567',
        'email' => 'new@example.com',
        'gender' => 'male',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'data' => ['name' => 'New Member']
        ]);

    $this->assertDatabaseHas('members', ['name' => 'New Member']);
});
