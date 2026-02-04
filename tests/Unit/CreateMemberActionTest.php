<?php

use App\Actions\CreateMemberAction;
use App\Models\Member;

beforeEach(function () {
    $this->action = app(CreateMemberAction::class);
});

test('create member returns member with given data', function () {
    $data = [
        'name' => 'John Doe',
        'document_number' => '12345678',
        'email' => 'john@example.com',
        'gender' => 'male',
    ];

    $member = $this->action->execute($data);

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->name)->toBe('John Doe')
        ->and($member->email)->toBe('john@example.com');
    $this->assertDatabaseHas('members', ['email' => 'john@example.com']);
});
