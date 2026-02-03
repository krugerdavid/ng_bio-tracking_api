<?php

namespace App\Http\Controllers\Api;

use App\Actions\UpdateMembershipPlanAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\MembershipPlanResource;
use App\Models\Member;
use App\Repositories\MembershipPlanRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    public function __construct(private MembershipPlanRepository $repository) {}

    public function showByMember(Request $request, string $memberId): JsonResponse
    {
        $member = Member::find($memberId);
        if (! $member) {
            return ApiResponse::error('Miembro no encontrado.', 404);
        }
        $this->authorize('view', $member);

        $plan = $this->repository->findByMember($memberId);

        if (! $plan) {
            return ApiResponse::error('Plan no encontrado.', 404);
        }
        $this->authorize('view', $plan);

        return ApiResponse::success(new MembershipPlanResource($plan));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'monthly_fee' => 'required|numeric|min:0',
            'weekly_frequency' => 'required|integer|min:1|max:5',
            'start_date' => 'required|date',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $member = Member::find($validated['member_id']);
        $this->authorize('view', $member);

        $plan = $this->repository->create($validated);

        return ApiResponse::success(new MembershipPlanResource($plan), 'Plan creado exitosamente.', 201);
    }

    public function update(Request $request, string $id, UpdateMembershipPlanAction $action): JsonResponse
    {
        $plan = $this->repository->find($id);
        if (! $plan) {
            return ApiResponse::error('Plan no encontrado.', 404);
        }
        $this->authorize('update', $plan);

        $validated = $request->validate([
            'monthly_fee' => 'sometimes|numeric',
            'weekly_frequency' => 'sometimes|integer|min:1|max:5',
            'start_date' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
        ]);

        $success = $action->execute($id, $validated);

        if (! $success) {
            return ApiResponse::error('No se pudo actualizar el plan.', 400);
        }

        return ApiResponse::success(new MembershipPlanResource($this->repository->find($id)), 'Plan actualizado exitosamente.');
    }
}
