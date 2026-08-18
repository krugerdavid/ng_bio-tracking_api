<?php

use App\Models\Member;
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
