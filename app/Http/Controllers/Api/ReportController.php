<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    /**
     * Miembros activos cuyo usuario nunca inició sesión.
     */
    public function engagement(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAccessAllMembers(), 403);

        $neverLoggedIn = Member::query()
            ->whereHas('user', function ($q) {
                $q->where('status', 'active')->whereNull('last_login_at');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'training_group'])
            ->map(fn (Member $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'training_group' => $m->training_group,
            ]);

        return ApiResponse::success([
            'never_logged_in_count' => $neverLoggedIn->count(),
            'never_logged_in' => $neverLoggedIn,
        ], 'Resumen de actividad.');
    }
}
