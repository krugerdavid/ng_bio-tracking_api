<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Ingresos mensuales (últimos 12 meses, solo pagos "paid") + saldo a favor total.
     */
    public function revenue(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $monthly = Payment::query()
            ->where('status', 'paid')
            ->where('month', '>=', now()->subMonths(11)->format('Y-m'))
            ->selectRaw('month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total' => round((float) $row->total, 2),
            ]);

        $creditBalanceTotal = round((float) Member::query()->sum('credit_balance'), 2);

        return ApiResponse::success([
            'monthly' => $monthly,
            'credit_balance_total' => $creditBalanceTotal,
        ], 'Resumen de ingresos.');
    }
}
