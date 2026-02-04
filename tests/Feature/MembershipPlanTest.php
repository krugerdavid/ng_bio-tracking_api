<?php

use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated user cannot get plan by member', function () {
    $member = Member::factory()->create();
    $response = $this->getJson('/api/members/' . $member->id . '/plan');
    $response->assertStatus(401);
});

test('plan by member returns 404 when member not found', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/members/00000000-0000-0000-0000-000000000000/plan');
    $response->assertStatus(404)->assertJson(['message' => 'Miembro no encontrado.']);
});

test('plan by member returns 404 when member has no plan', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/members/' . $member->id . '/plan');
    $response->assertStatus(404)->assertJson(['message' => 'Plan no encontrado.']);
});

test('admin can get plan by member', function () {
    $member = Member::factory()->create();
    $plan = MembershipPlan::factory()->for($member)->create();
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/members/' . $member->id . '/plan');
    $response->assertStatus(200)->assertJson(['data' => ['id' => $plan->id]]);
});

test('admin can create membership plan', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->postJson('/api/plans', [
        'member_id' => $member->id,
        'monthly_fee' => 80,
        'weekly_frequency' => 3,
        'start_date' => now()->toDateString(),
    ]);
    $response->assertStatus(201)->assertJson(['status' => 'success']);
    $this->assertDatabaseHas('membership_plans', ['member_id' => $member->id]);
});

test('create plan returns 422 when validation fails', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->postJson('/api/plans', [
        'member_id' => 'invalid',
        'monthly_fee' => -1,
        'weekly_frequency' => 10,
    ]);
    $response->assertStatus(422)->assertJsonValidationErrors(['member_id', 'monthly_fee', 'weekly_frequency', 'start_date']);
});

test('update plan returns 404 for non-existent id', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->putJson('/api/plans/00000000-0000-0000-0000-000000000000', ['monthly_fee' => 100]);
    $response->assertStatus(404)->assertJson(['message' => 'Plan no encontrado.']);
});
