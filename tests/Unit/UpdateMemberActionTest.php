<?php

use App\Actions\UpdateMemberAction;
use App\Models\Member;

beforeEach(function () {
    $this->action = app(UpdateMemberAction::class);
});

test('update member returns true and updates data', function () {
    $member = Member::factory()->create(['name' => 'Old Name']);

    $result = $this->action->execute($member->id, ['name' => 'New Name']);

    expect($result)->toBeTrue();
    $this->assertDatabaseHas('members', ['id' => $member->id, 'name' => 'New Name']);
});

test('update member returns false for non-existent id', function () {
    $result = $this->action->execute('00000000-0000-0000-0000-000000000000', ['name' => 'X']);

    expect($result)->toBeFalse();
});
