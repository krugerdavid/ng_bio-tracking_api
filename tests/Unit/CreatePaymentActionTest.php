<?php

use App\Actions\CreatePaymentAction;
use App\Models\Member;
use App\Models\Payment;

beforeEach(function () {
    $this->action = app(CreatePaymentAction::class);
});

test('create payment returns payment with given data', function () {
    $member = Member::factory()->create();
    $data = [
        'member_id' => $member->id,
        'month' => '2025-01',
        'amount' => 99.50,
        'payment_date' => '2025-01-15',
        'status' => 'paid',
        'notes' => 'Test note',
    ];

    $payment = $this->action->execute($data);

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->member_id)->toBe($member->id)
        ->and($payment->amount)->toBe(99.50)
        ->and($payment->status)->toBe('paid');
    $this->assertDatabaseHas('payments', ['member_id' => $member->id, 'month' => '2025-01']);
});
