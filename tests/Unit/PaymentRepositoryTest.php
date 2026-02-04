<?php

use App\Models\Member;
use App\Models\Payment;
use App\Repositories\PaymentRepository;

beforeEach(function () {
    $this->repository = app(PaymentRepository::class);
});

test('findByMonth returns payments for given month', function () {
    $member = Member::factory()->create();
    Payment::factory()->for($member)->create(['month' => '2025-01']);
    Payment::factory()->for($member)->create(['month' => '2025-01']);
    Payment::factory()->for($member)->create(['month' => '2025-02']);

    $result = $this->repository->findByMonth('2025-01');

    expect($result)->toHaveCount(2)
        ->and($result->pluck('month')->unique()->first())->toBe('2025-01');
});
