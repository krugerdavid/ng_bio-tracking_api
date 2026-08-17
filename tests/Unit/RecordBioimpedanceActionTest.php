<?php

use App\Actions\RecordBioimpedanceAction;
use App\Models\Bioimpedance;
use App\Models\Member;
use App\Models\User;

beforeEach(function () {
    $this->action = app(RecordBioimpedanceAction::class);
});

test('record bioimpedance returns record with given data', function () {
    $member = Member::factory()->create();
    $admin = User::factory()->admin()->create();
    $data = [
        'member_id' => $member->id,
        'date' => '2025-01-10',
        'height' => 1.75,
        'weight' => 70,
        'imc' => 22.86,
        'body_fat_percentage' => 18,
        'muscle_mass_percentage' => 42,
        'kcal' => 1800,
        'metabolic_age' => 35,
        'visceral_fat_percentage' => 5,
    ];

    $record = $this->action->execute($data, $admin);

    expect($record)->toBeInstanceOf(Bioimpedance::class)
        ->and($record->member_id)->toBe($member->id)
        ->and($record->weight)->toBe(70.0)
        ->and($record->status->value)->toBe('confirmed');
    $this->assertDatabaseHas('bioimpedances', ['member_id' => $member->id]);
});

test('record bioimpedance forces own member_id and pending status for a self-entry', function () {
    $member = Member::factory()->create();
    $user = User::factory()->member()->create();
    $member->update(['user_id' => $user->id]);
    $user->refresh();

    $otherMember = Member::factory()->create();
    $data = [
        'member_id' => $otherMember->id,
        'status' => 'confirmed',
        'date' => '2025-01-10',
        'weight' => 70,
    ];

    $record = $this->action->execute($data, $user);

    expect($record->member_id)->toBe($member->id)
        ->and($record->status->value)->toBe('pending');
});
