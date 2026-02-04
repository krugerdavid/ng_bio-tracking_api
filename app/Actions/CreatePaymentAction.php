<?php

namespace App\Actions;

use App\Models\Member;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\MemberDebtService;

class CreatePaymentAction implements Action
{
    public function __construct(
        private PaymentRepository $repository,
        private MemberDebtService $debtService
    ) {}

    /**
     * Crea el pago y, si el monto es mayor que la cuota del mes, suma el excedente al saldo a favor del miembro.
     *
     * @param array $data
     * @return Payment
     */
    public function execute(...$args): Payment
    {
        [$data] = $args;
        $payment = $this->repository->create($data);

        if (($data['status'] ?? '') === 'paid') {
            $this->addExcessToCreditBalance($payment);
        }

        return $payment;
    }

    private function addExcessToCreditBalance(Payment $payment): void
    {
        $member = Member::with('membershipPlan')->find($payment->member_id);
        if (! $member || ! $member->membershipPlan?->is_active) {
            return;
        }

        $monthlyFee = $this->debtService->getMonthlyFee($member);
        if ($monthlyFee <= 0) {
            return;
        }

        $amount = (float) $payment->amount;
        if ($amount <= $monthlyFee) {
            return;
        }

        $excess = $amount - $monthlyFee;
        $member->increment('credit_balance', $excess);
    }
}
