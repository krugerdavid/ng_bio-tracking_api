<?php

use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated user cannot view revenue report', function () {
    $this->getJson('/api/reports/revenue')->assertStatus(401);
});

test('member role cannot view revenue report', function () {
    Sanctum::actingAs(User::factory()->member()->create());
    $this->getJson('/api/reports/revenue')->assertStatus(403);
});

test('admin sees monthly revenue grouped and credit balance total', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $thisMonth = now()->format('Y-m');
    $lastMonth = now()->subMonth()->format('Y-m');

    Payment::factory()->create(['month' => $thisMonth, 'amount' => 100, 'status' => 'paid']);
    Payment::factory()->create(['month' => $thisMonth, 'amount' => 50, 'status' => 'paid']);
    Payment::factory()->create(['month' => $lastMonth, 'amount' => 200, 'status' => 'paid']);
    // Not paid: should be ignored.
    Payment::factory()->create(['month' => $thisMonth, 'amount' => 999, 'status' => 'pending']);

    Member::factory()->create(['credit_balance' => 30]);
    Member::factory()->create(['credit_balance' => 20]);

    $response = $this->getJson('/api/reports/revenue');

    $response->assertStatus(200)
        ->assertJsonPath('data.credit_balance_total', 50);

    $monthly = collect($response->json('data.monthly'))->keyBy('month');
    expect((float) $monthly[$thisMonth]['total'])->toBe(150.0);
    expect((float) $monthly[$lastMonth]['total'])->toBe(200.0);
});

test('revenue report ignores payments older than 12 months', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $oldMonth = now()->subMonths(20)->format('Y-m');
    Payment::factory()->create(['month' => $oldMonth, 'amount' => 500, 'status' => 'paid']);

    $response = $this->getJson('/api/reports/revenue');

    $months = collect($response->json('data.monthly'))->pluck('month');
    expect($months->contains($oldMonth))->toBeFalse();
});

test('unauthenticated user cannot view engagement report', function () {
    $this->getJson('/api/reports/engagement')->assertStatus(401);
});

test('member role cannot view engagement report', function () {
    Sanctum::actingAs(User::factory()->member()->create());
    $this->getJson('/api/reports/engagement')->assertStatus(403);
});

test('engagement report lists active members who never logged in', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $neverLoggedInUser = User::factory()->member()->create(['status' => 'active', 'last_login_at' => null]);
    $neverLoggedInMember = Member::factory()->create(['user_id' => $neverLoggedInUser->id, 'name' => 'Ana Fantasma']);

    $loggedInUser = User::factory()->member()->create(['status' => 'active', 'last_login_at' => now()]);
    Member::factory()->create(['user_id' => $loggedInUser->id, 'name' => 'Beto Activo']);

    $pendingUser = User::factory()->member()->create(['status' => 'pending', 'last_login_at' => null]);
    Member::factory()->create(['user_id' => $pendingUser->id, 'name' => 'Caro Pendiente']);

    $response = $this->getJson('/api/reports/engagement');

    $response->assertStatus(200)->assertJsonPath('data.never_logged_in_count', 1);

    $names = collect($response->json('data.never_logged_in'))->pluck('name');
    expect($names)->toContain('Ana Fantasma');
    expect($names)->not->toContain('Beto Activo');
    expect($names)->not->toContain('Caro Pendiente');
    expect($response->json('data.never_logged_in.0.id'))->toBe($neverLoggedInMember->id);
});

test('unauthenticated user cannot view data quality report', function () {
    $this->getJson('/api/reports/data-quality')->assertStatus(401);
});

test('member role cannot view data quality report', function () {
    Sanctum::actingAs(User::factory()->member()->create());
    $this->getJson('/api/reports/data-quality')->assertStatus(403);
});

test('data quality report flags members missing plan and/or email', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $complete = Member::factory()->create(['name' => 'Completo', 'email' => 'completo@example.com']);
    MembershipPlan::factory()->create(['member_id' => $complete->id]);

    $noPlan = Member::factory()->create(['name' => 'Sin Plan', 'email' => 'sinplan@example.com']);

    $noEmail = Member::factory()->create(['name' => 'Sin Email', 'email' => null]);
    MembershipPlan::factory()->create(['member_id' => $noEmail->id]);

    $noPlanNoEmail = Member::factory()->create(['name' => 'Sin Nada', 'email' => null]);

    $response = $this->getJson('/api/reports/data-quality');

    $response->assertStatus(200)->assertJsonPath('data.incomplete_count', 3);

    $incomplete = collect($response->json('data.incomplete'))->keyBy('name');
    expect($incomplete->has('Completo'))->toBeFalse();

    expect($incomplete['Sin Plan']['missing_plan'])->toBeTrue();
    expect($incomplete['Sin Plan']['missing_email'])->toBeFalse();

    expect($incomplete['Sin Email']['missing_plan'])->toBeFalse();
    expect($incomplete['Sin Email']['missing_email'])->toBeTrue();

    expect($incomplete['Sin Nada']['missing_plan'])->toBeTrue();
    expect($incomplete['Sin Nada']['missing_email'])->toBeTrue();
});

test('unauthenticated user cannot view member summaries report', function () {
    $this->postJson('/api/reports/member-summaries')->assertStatus(401);
});

test('member role cannot view member summaries report', function () {
    Sanctum::actingAs(User::factory()->member()->create());
    $this->postJson('/api/reports/member-summaries')->assertStatus(403);
});

test('member summaries report returns plan and debt for each requested member in one call', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $withPlan = Member::factory()->create(['name' => 'Con Plan', 'credit_balance' => 0]);
    $plan = MembershipPlan::factory()->create([
        'member_id' => $withPlan->id,
        'weekly_frequency' => 3,
        'is_active' => true,
        'start_date' => now()->subMonths(2)->startOfMonth(),
    ]);

    $withoutPlan = Member::factory()->create(['name' => 'Sin Plan']);

    // Not requested: should not appear even though it exists.
    Member::factory()->create(['name' => 'No Pedido']);

    $response = $this->postJson('/api/reports/member-summaries', [
        'member_ids' => [$withPlan->id, $withoutPlan->id],
    ]);

    $response->assertStatus(200);

    $items = collect($response->json('data'))->keyBy('member_id');
    expect($items)->toHaveCount(2);

    expect($items[$withPlan->id]['plan']['id'])->toBe($plan->id);
    expect($items[$withPlan->id]['plan']['weekly_frequency'])->toBe(3);
    expect($items[$withPlan->id]['debt'])->toHaveKey('total_debt_after_credit');

    expect($items[$withoutPlan->id]['plan'])->toBeNull();
    expect((float) $items[$withoutPlan->id]['debt']['total_debt_after_credit'])->toBe(0.0);
});

test('member summaries report returns all members when no ids are given', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    Member::factory()->count(3)->create();

    $response = $this->postJson('/api/reports/member-summaries');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);
});
