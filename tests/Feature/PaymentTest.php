<?php

use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated user cannot list payments for member', function () {
    $member = Member::factory()->create();
    $response = $this->getJson('/api/members/' . $member->id . '/payments');
    $response->assertStatus(401);
});

test('payments index returns 404 when member not found', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/members/00000000-0000-0000-0000-000000000000/payments');
    $response->assertStatus(404)->assertJson(['message' => 'Miembro no encontrado.']);
});

test('admin can list payments for member', function () {
    $member = Member::factory()->create();
    Payment::factory()->count(2)->for($member)->create();
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/members/' . $member->id . '/payments');
    $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
});

test('admin can create payment', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->postJson('/api/payments', [
        'member_id' => $member->id,
        'month' => now()->format('Y-m'),
        'amount' => 50.00,
        'payment_date' => now()->toDateString(),
        'status' => 'paid',
    ]);
    $response->assertStatus(201)->assertJson(['status' => 'success']);
    $this->assertDatabaseHas('payments', ['member_id' => $member->id]);
});

test('create payment returns 422 when validation fails', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->postJson('/api/payments', [
        'member_id' => 'invalid-uuid',
        'month' => 'invalid',
        'amount' => 'not-numeric',
        'payment_date' => null,
        'status' => 'invalid',
    ]);
    $response->assertStatus(422)->assertJsonValidationErrors(['member_id', 'month', 'amount', 'payment_date', 'status']);
});

test('show payment returns 404 for non-existent id', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/payments/00000000-0000-0000-0000-000000000000');
    $response->assertStatus(404)->assertJson(['message' => 'Pago no encontrado.']);
});

test('member role cannot see other member payments', function () {
    $memberA = Member::factory()->create();
    $userA = User::factory()->member()->create();
    $memberA->update(['user_id' => $userA->id]);
    $memberB = Member::factory()->create();
    $paymentB = Payment::factory()->for($memberB)->create();
    Sanctum::actingAs($userA);
    $response = $this->getJson('/api/payments/' . $paymentB->id);
    $response->assertStatus(403);
});

test('member role can see own member payment', function () {
    $member = Member::factory()->create();
    $user = User::factory()->member()->create();
    $member->update(['user_id' => $user->id]);
    $payment = Payment::factory()->for($member)->create();
    Sanctum::actingAs($user);
    $response = $this->getJson('/api/payments/' . $payment->id);
    $response->assertStatus(200)->assertJson(['data' => ['id' => $payment->id]]);
});

test('payment with amount greater than monthly fee adds excess to member credit_balance', function () {
    $member = Member::factory()->create();
    \App\Models\MembershipPlan::factory()->for($member)->create(['monthly_fee' => 50, 'is_active' => true]);
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->postJson('/api/payments', [
        'member_id' => $member->id,
        'month' => now()->format('Y-m'),
        'amount' => 80,
        'payment_date' => now()->toDateString(),
        'status' => 'paid',
    ]);
    $response->assertStatus(201);
    $member->refresh();
    expect((float) $member->credit_balance)->toBe(30.0);
});
