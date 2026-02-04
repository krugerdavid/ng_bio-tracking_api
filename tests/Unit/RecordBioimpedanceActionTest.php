<?php

use App\Actions\RecordBioimpedanceAction;
use App\Models\Bioimpedance;
use App\Models\Member;

beforeEach(function () {
    $this->action = app(RecordBioimpedanceAction::class);
});

test('record bioimpedance returns record with given data', function () {
    $member = Member::factory()->create();
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

    $record = $this->action->execute($data);

    expect($record)->toBeInstanceOf(Bioimpedance::class)
        ->and($record->member_id)->toBe($member->id)
        ->and($record->weight)->toBe(70.0);
    $this->assertDatabaseHas('bioimpedances', ['member_id' => $member->id]);
});
