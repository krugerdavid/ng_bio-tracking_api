<?php

use App\Enums\Role;

test('Role assignableValues returns admin and member', function () {
    $values = Role::assignableValues();
    expect($values)->toContain('admin')
        ->toContain('member')
        ->not->toContain('root');
});

test('Role label returns Spanish labels', function () {
    expect(Role::Root->label())->toBe('Root')
        ->and(Role::Admin->label())->toBe('Administrador')
        ->and(Role::Member->label())->toBe('Miembro');
});
