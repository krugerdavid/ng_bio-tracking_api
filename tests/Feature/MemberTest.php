<?php

use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated user cannot list members', function () {
    $response = $this->getJson('/api/members');
    $response->assertStatus(401)
        ->assertJson(['message' => 'No autenticado.']);
});

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

test('create member returns 422 when validation fails', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->postJson('/api/members', [
        'name' => '',
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email']);
});

test('member role cannot create another member', function () {
    $memberUser = User::factory()->member()->create();
    Sanctum::actingAs($memberUser);

    $response = $this->postJson('/api/members', [
        'name' => 'Other Member',
        'document_number' => '999',
        'email' => 'other@example.com',
    ]);

    $response->assertStatus(403);
});

test('show member returns 404 for non-existent id', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/members/00000000-0000-0000-0000-000000000000');
    $response->assertStatus(404)->assertJson(['message' => 'Miembro no encontrado.']);
});

test('member role cannot view another member', function () {
    $memberA = Member::factory()->create();
    $userA = User::factory()->member()->create();
    $memberA->update(['user_id' => $userA->id]);
    $memberB = Member::factory()->create();
    Sanctum::actingAs($userA);
    $response = $this->getJson('/api/members/' . $memberB->id);
    $response->assertStatus(403);
});

test('member role can view own member', function () {
    $member = Member::factory()->create();
    $user = User::factory()->member()->create();
    $member->update(['user_id' => $user->id]);
    Sanctum::actingAs($user);
    $response = $this->getJson('/api/members/' . $member->id);
    $response->assertStatus(200)->assertJson(['data' => ['id' => $member->id]]);
});

test('update member returns 404 for non-existent id', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->putJson('/api/members/00000000-0000-0000-0000-000000000000', ['name' => 'Updated']);
    $response->assertStatus(404);
});

test('destroy member returns 404 for non-existent id', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->deleteJson('/api/members/00000000-0000-0000-0000-000000000000');
    $response->assertStatus(404);
});

test('debt summary returns monthly_fee owed_months total_debt credit_balance total_debt_after_credit', function () {
    $member = Member::factory()->create();
    MembershipPlan::factory()->for($member)->create([
        'monthly_fee' => 100,
        'start_date' => now()->subMonths(2),
        'is_active' => true,
    ]);
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/members/' . $member->id . '/debt');
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'monthly_fee',
                'owed_months',
                'months_owed',
                'total_debt',
                'credit_balance',
                'total_debt_after_credit',
            ],
        ])
        ->assertJsonPath('data.monthly_fee', 100)
        ->assertJsonPath('data.credit_balance', 0);
});

test('member resource includes credit_balance', function () {
    $member = Member::factory()->create(['credit_balance' => 25.5]);
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/members/' . $member->id);
    $response->assertStatus(200)->assertJsonPath('data.credit_balance', 25.5);
});
