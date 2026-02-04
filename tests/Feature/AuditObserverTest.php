<?php

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('creating a member creates an audit log entry when audit is enabled', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $initialCount = AuditLog::count();

    Member::create([
        'name' => 'Audit Test',
        'document_number' => '999',
        'email' => 'audit@example.com',
        'gender' => 'male',
    ]);

    expect(AuditLog::count())->toBe($initialCount + 1);
    $log = AuditLog::latest()->first();
    expect($log->event)->toBe('created')
        ->and($log->auditable_type)->toContain('Member')
        ->and($log->new_values)->toHaveKey('name')
        ->and($log->new_values['name'])->toBe('Audit Test');
});

test('updating a member creates an audit log entry', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $member = Member::factory()->create(['name' => 'Original']);
    $initialCount = AuditLog::count();

    $member->update(['name' => 'Updated']);

    expect(AuditLog::count())->toBe($initialCount + 1);
    $log = AuditLog::latest()->first();
    expect($log->event)->toBe('updated')
        ->and($log->old_values)->toHaveKey('name')
        ->and($log->old_values['name'])->toBe('Original')
        ->and($log->new_values['name'])->toBe('Updated');
});

test('deleting a member creates an audit log entry', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $member = Member::factory()->create(['name' => 'To Delete']);
    $initialCount = AuditLog::count();

    $member->delete();

    expect(AuditLog::count())->toBe($initialCount + 1);
    $log = AuditLog::latest()->first();
    expect($log->event)->toBe('deleted')
        ->and($log->old_values)->toHaveKey('name')
        ->and($log->new_values)->toBeNull();
});
