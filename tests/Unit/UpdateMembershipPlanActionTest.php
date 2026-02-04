<?php

use App\Actions\UpdateMembershipPlanAction;
use App\Models\Member;
use App\Models\MembershipPlan;

beforeEach(function () {
    $this->action = app(UpdateMembershipPlanAction::class);
});

test('update membership plan returns true and updates data', function () {
    $member = Member::factory()->create();
    $plan = MembershipPlan::factory()->for($member)->create(['monthly_fee' => 50]);

    $result = $this->action->execute($plan->id, ['monthly_fee' => 75]);

    expect($result)->toBeTrue();
    $plan->refresh();
    expect($plan->monthly_fee)->toBe(75.0);
});

test('update membership plan returns false for non-existent id', function () {
    $result = $this->action->execute('00000000-0000-0000-0000-000000000000', ['monthly_fee' => 100]);

    expect($result)->toBeFalse();
});
