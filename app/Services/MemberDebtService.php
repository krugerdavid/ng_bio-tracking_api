<?php

namespace App\Services;

use App\Models\Member;
use Carbon\Carbon;

class MemberDebtService
{
    /**
     * Resumen de deuda del miembro: meses adeudados, total, saldo a favor y deuda final.
     *
     * @return array{monthly_fee: float, owed_months: list<string>, months_owed: int, total_debt: float, credit_balance: float, total_debt_after_credit: float}
     */
    public function getDebtSummary(Member $member): array
    {
        $member->loadMissing('membershipPlan', 'payments');
        $plan = $member->membershipPlan;

        $monthlyFee = 0.0;
        $owedMonths = [];

        if ($plan && $plan->is_active) {
            $monthlyFee = (float) $plan->monthly_fee;
            $owedMonths = $this->computeOwedMonths($member);
        }

        $totalDebt = count($owedMonths) * $monthlyFee;
        $creditBalance = (float) ($member->credit_balance ?? 0);
        $totalDebtAfterCredit = max(0, $totalDebt - $creditBalance);

        return [
            'monthly_fee' => round($monthlyFee, 2),
            'owed_months' => $owedMonths,
            'months_owed' => count($owedMonths),
            'total_debt' => round($totalDebt, 2),
            'credit_balance' => round($creditBalance, 2),
            'total_debt_after_credit' => round($totalDebtAfterCredit, 2),
        ];
    }

    /**
     * Cuota mensual del miembro (0 si no tiene plan activo).
     */
    public function getMonthlyFee(Member $member): float
    {
        $member->loadMissing('membershipPlan');
        $plan = $member->membershipPlan;

        if (! $plan || ! $plan->is_active) {
            return 0.0;
        }

        return (float) $plan->monthly_fee;
    }

    /**
     * Lista de meses (YYYY-MM) que el miembro tiene en mora (sin pago con status 'paid').
     */
    private function computeOwedMonths(Member $member): array
    {
        $plan = $member->membershipPlan;
        if (! $plan) {
            return [];
        }

        $start = Carbon::parse($plan->start_date)->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        if ($start->gt($end)) {
            return [];
        }

        $paidMonths = $member->payments()
            ->where('status', 'paid')
            ->pluck('month')
            ->map(fn (string $m) => (string) $m)
            ->flip()
            ->all();

        $owed = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $key = $current->format('Y-m');
            if (! isset($paidMonths[$key])) {
                $owed[] = $key;
            }
            $current->addMonth();
        }

        return $owed;
    }
}
