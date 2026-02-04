<?php

use App\Models\Bioimpedance;
use App\Models\Member;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated user cannot list bioimpedance for member', function () {
    $member = Member::factory()->create();
    $response = $this->getJson('/api/members/' . $member->id . '/bioimpedance');
    $response->assertStatus(401);
});

test('bioimpedance index returns 404 when member not found', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/members/00000000-0000-0000-0000-000000000000/bioimpedance');
    $response->assertStatus(404)->assertJson(['message' => 'Miembro no encontrado.']);
});

test('admin can list bioimpedance for member', function () {
    $member = Member::factory()->create();
    Bioimpedance::factory()->count(2)->for($member)->create();
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/members/' . $member->id . '/bioimpedance');
    $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
});

test('admin can create bioimpedance record', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->postJson('/api/bioimpedances', [
        'member_id' => $member->id,
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
    $response->assertStatus(201)->assertJson(['status' => 'success']);
    $this->assertDatabaseHas('bioimpedances', ['member_id' => $member->id]);
});

test('create bioimpedance returns 422 when validation fails', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->postJson('/api/bioimpedances', [
        'member_id' => 'invalid',
        'date' => 'not-a-date',
    ]);
    $response->assertStatus(422)->assertJsonValidationErrors(['member_id', 'date', 'height', 'weight', 'imc', 'body_fat_percentage', 'muscle_mass_percentage', 'kcal', 'metabolic_age', 'visceral_fat_percentage']);
});

test('show bioimpedance returns 404 for non-existent id', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/bioimpedances/00000000-0000-0000-0000-000000000000');
    $response->assertStatus(404)->assertJson(['message' => 'Registro no encontrado.']);
});
