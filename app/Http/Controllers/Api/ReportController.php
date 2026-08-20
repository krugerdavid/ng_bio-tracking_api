<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Member;
use App\Models\Payment;
use App\Services\MemberDebtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private MemberDebtService $debtService
    ) {}

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

    /**
     * Miembros con datos incompletos: sin plan de membresía configurado y/o sin email.
     */
    public function dataQuality(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAccessAllMembers(), 403);

        $incomplete = Member::query()
            ->where(function ($q) {
                $q->whereDoesntHave('membershipPlan')->orWhereNull('email');
            })
            ->with('membershipPlan')
            ->orderBy('name')
            ->get()
            ->map(fn (Member $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'training_group' => $m->training_group,
                'missing_plan' => $m->membershipPlan === null,
                'missing_email' => $m->email === null,
            ]);

        return ApiResponse::success([
            'incomplete_count' => $incomplete->count(),
            'incomplete' => $incomplete,
        ], 'Resumen de datos incompletos.');
    }

    /**
     * Plan y resumen de deuda de un conjunto de miembros en una sola consulta.
     *
     * Reemplaza el patrón N+1 del frontend (una llamada a GET /members/{id}/plan
     * y otra a GET /members/{id}/debt por cada miembro listado en /members o en
     * el dashboard) por una sola request con los planes y pagos precargados.
     */
    public function memberSummaries(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAccessAllMembers(), 403);

        $ids = array_values(array_filter((array) $request->input('member_ids', [])));

        $members = Member::query()
            ->when(count($ids) > 0, fn ($q) => $q->whereIn('id', $ids))
            ->with(['membershipPlan', 'payments'])
            ->get();

        $items = $members->map(function (Member $member) {
            $plan = $member->membershipPlan;

            return [
                'member_id' => $member->id,
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'member_id' => $plan->member_id,
                    'monthly_fee' => $plan->monthly_fee,
                    'weekly_frequency' => $plan->weekly_frequency,
                    'start_date' => $plan->start_date?->format('Y-m-d'),
                    'is_active' => $plan->is_active,
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at,
                ] : null,
                'debt' => $this->debtService->getDebtSummary($member),
            ];
        });

        return ApiResponse::success($items, 'Resumen de planes y deuda por miembro.');
    }
}
