<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateMemberAction;
use App\Actions\DeleteMemberAction;
use App\Actions\UpdateMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMemberRequest;
use App\Http\Requests\Api\UpdateMemberRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\MemberResource;
use App\Repositories\MemberRepository;
use App\Services\MemberDebtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(
        private MemberRepository $repository,
        private MemberDebtService $debtService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Member::class);

        $members = $this->repository->searchForUser(
            $request->user(),
            $request->query('search'),
            max(1, min(100, (int) $request->query('page_size', 15)))
        );

        return ApiResponse::success(
            MemberResource::collection($members)->response()->getData(true),
            'Miembros recuperados exitosamente.'
        );
    }

    public function store(StoreMemberRequest $request, CreateMemberAction $action): JsonResponse
    {
        $member = $action->execute($request->validated());

        return ApiResponse::success(new MemberResource($member), 'Miembro creado exitosamente.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $member = $this->repository->find($id);

        if (! $member) {
            return ApiResponse::error('Miembro no encontrado.', 404);
        }

        $this->authorize('view', $member);

        $member->load('membershipPlan');

        return ApiResponse::success(new MemberResource($member));
    }

    public function update(UpdateMemberRequest $request, string $id, UpdateMemberAction $action): JsonResponse
    {
        $member = $this->repository->find($id);
        if (! $member) {
            return ApiResponse::error('Miembro no encontrado.', 404);
        }

        $success = $action->execute($id, $request->validated());

        if (! $success) {
            return ApiResponse::error('No se pudo actualizar el miembro.', 400);
        }

        return ApiResponse::success(new MemberResource($this->repository->find($id)), 'Miembro actualizado exitosamente.');
    }

    public function destroy(string $id, DeleteMemberAction $action): JsonResponse
    {
        $member = $this->repository->find($id);
        if (! $member) {
            return ApiResponse::error('Miembro no encontrado.', 404);
        }
        $this->authorize('delete', $member);

        $success = $action->execute($id);

        if (! $success) {
            return ApiResponse::error('No se pudo eliminar el miembro.', 400);
        }

        return ApiResponse::success(null, 'Miembro eliminado exitosamente.');
    }

    /**
     * Resumen de deuda del miembro: meses adeudados, total, saldo a favor y deuda tras descontar crédito.
     */
    public function debtSummary(string $memberId): JsonResponse
    {
        $member = $this->repository->find($memberId);
        if (! $member) {
            return ApiResponse::error('Miembro no encontrado.', 404);
        }
        $this->authorize('view', $member);

        $summary = $this->debtService->getDebtSummary($member);

        return ApiResponse::success($summary, 'Resumen de deuda.');
    }
}
