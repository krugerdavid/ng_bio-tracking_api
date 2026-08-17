<?php

use App\Models\Bioimpedance;
use App\Models\Member;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->member = Member::factory()->create();
    $this->user = User::factory()->member()->create();
    $this->member->update(['user_id' => $this->user->id]);
    Sanctum::actingAs($this->user->fresh());
});

test('member can create own weight-only entry as pending', function () {
    $response = $this->postJson('/api/bioimpedances', [
        'date' => now()->toDateString(),
        'weight' => 72.5,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.member_id', $this->member->id)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.weight', 72.5);

    $this->assertDatabaseHas('bioimpedances', [
        'member_id' => $this->member->id,
        'status' => 'pending',
    ]);
});

test('member cannot create a record for another member', function () {
    $otherMember = Member::factory()->create();

    $response = $this->postJson('/api/bioimpedances', [
        'member_id' => $otherMember->id,
        'date' => now()->toDateString(),
        'weight' => 72.5,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.member_id', $this->member->id);
    $this->assertDatabaseMissing('bioimpedances', ['member_id' => $otherMember->id]);
});

test('member cannot set status to confirmed when creating', function () {
    $response = $this->postJson('/api/bioimpedances', [
        'date' => now()->toDateString(),
        'weight' => 72.5,
        'status' => 'confirmed',
    ]);

    $response->assertStatus(201)->assertJsonPath('data.status', 'pending');
});

test('member can edit and delete their own entry while pending', function () {
    $record = Bioimpedance::factory()->for($this->member)->create(['status' => 'pending', 'weight' => 70]);

    $this->putJson('/api/bioimpedances/'.$record->id, ['weight' => 71])
        ->assertStatus(200)
        ->assertJsonPath('data.weight', 71);

    $this->deleteJson('/api/bioimpedances/'.$record->id)->assertStatus(200);
    $this->assertDatabaseMissing('bioimpedances', ['id' => $record->id]);
});

test('member cannot edit or delete their own entry once confirmed', function () {
    $record = Bioimpedance::factory()->for($this->member)->create(['status' => 'confirmed']);

    $this->putJson('/api/bioimpedances/'.$record->id, ['weight' => 71])->assertStatus(403);
    $this->deleteJson('/api/bioimpedances/'.$record->id)->assertStatus(403);
});

test('member cannot confirm their own pending entry via update', function () {
    $record = Bioimpedance::factory()->for($this->member)->create(['status' => 'pending']);

    $this->putJson('/api/bioimpedances/'.$record->id, ['status' => 'confirmed'])
        ->assertStatus(200);

    expect($record->fresh()->status->value)->toBe('pending');
});

test('admin can confirm a pending entry', function () {
    $record = Bioimpedance::factory()->for($this->member)->create(['status' => 'pending']);
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->putJson('/api/bioimpedances/'.$record->id, ['status' => 'confirmed'])
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'confirmed');
});

test('admin create still defaults to confirmed with full metrics', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->postJson('/api/bioimpedances', [
        'member_id' => $this->member->id,
        'date' => now()->toDateString(),
        'height' => 1.75,
        'weight' => 70,
        'imc' => 22.86,
        'body_fat_percentage' => 18,
        'muscle_mass_percentage' => 42,
        'kcal' => 1800,
        'metabolic_age' => 35,
        'visceral_fat_percentage' => 5,
    ]);

    $response->assertStatus(201)->assertJsonPath('data.status', 'confirmed');
});
