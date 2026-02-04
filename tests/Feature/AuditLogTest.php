<?php

use App\Models\AuditLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated user cannot list audit logs', function () {
    $response = $this->getJson('/api/audit-logs');
    $response->assertStatus(401);
});

test('admin cannot list audit logs', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/audit-logs');
    $response->assertStatus(403);
});

test('member cannot list audit logs', function () {
    Sanctum::actingAs(User::factory()->member()->create());
    $response = $this->getJson('/api/audit-logs');
    $response->assertStatus(403);
});

test('root can list audit logs', function () {
    Sanctum::actingAs(User::factory()->root()->create());
    $response = $this->getJson('/api/audit-logs');
    $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
});

test('show audit log returns 404 for non-existent id', function () {
    Sanctum::actingAs(User::factory()->root()->create());
    $response = $this->getJson('/api/audit-logs/99999');
    $response->assertStatus(404)->assertJson(['message' => 'Entrada de auditoría no encontrada.']);
});

test('root can show audit log', function () {
    $user = User::factory()->root()->create();
    Sanctum::actingAs($user);
    $log = AuditLog::create([
        'event' => 'created',
        'auditable_type' => 'App\Models\Member',
        'auditable_id' => 'test-uuid',
        'old_values' => null,
        'new_values' => ['name' => 'Test'],
        'user_id' => $user->id,
    ]);
    $response = $this->getJson('/api/audit-logs/' . $log->id);
    $response->assertStatus(200)->assertJson(['data' => ['id' => $log->id]]);
});

test('admin cannot show single audit log', function () {
    $root = User::factory()->root()->create();
    $log = AuditLog::create([
        'event' => 'created',
        'auditable_type' => 'App\Models\Member',
        'auditable_id' => 'test',
        'user_id' => $root->id,
    ]);
    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/audit-logs/' . $log->id);
    $response->assertStatus(403);
});
