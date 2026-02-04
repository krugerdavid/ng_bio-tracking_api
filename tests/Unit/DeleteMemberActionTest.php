<?php

use App\Actions\DeleteMemberAction;
use App\Models\Member;

beforeEach(function () {
    $this->action = app(DeleteMemberAction::class);
});

test('delete member returns true and removes record', function () {
    $member = Member::factory()->create();

    $result = $this->action->execute($member->id);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('members', ['id' => $member->id]);
});

test('delete member returns false for non-existent id', function () {
    $result = $this->action->execute('00000000-0000-0000-0000-000000000000');

    expect($result)->toBeFalse();
});
