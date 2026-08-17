<?php

namespace App\Http\Controllers\Api;

use App\Actions\ApproveMemberAction;
use App\Actions\CreateMemberAction;
use App\Actions\DeleteMemberAction;
use App\Actions\InviteMemberAction;
use App\Actions\UpdateMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InviteMemberRequest;
use App\Http\Requests\Api\StoreMemberRequest;
use App\Http\Requests\Api\UpdateMemberRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\MemberResource;
use App\Repositories\MemberRepository;
use App\Services\MemberDebtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            max(1, min(100, (int) $request->query('page_size', 15))),
            $request->query('training_group'),
            $request->query('status')
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

        $member->load('membershipPlan', 'user');

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

    /**
     * Invitar (o reenviar) acceso a la app: crea/vincula User role=member y envía email.
     */
    public function invite(InviteMemberRequest $request, string $member, InviteMemberAction $action): JsonResponse
    {
        $model = $this->repository->find($member);
        if (! $model) {
            return ApiResponse::error('Miembro no encontrado.', 404);
        }

        try {
            $result = $action->execute($model, $request->validated());
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }

        $message = $result['resent']
            ? 'Invitación reenviada a '.$result['email'].'.'
            : 'Invitación enviada a '.$result['email'].'.';

        return ApiResponse::success(
            new MemberResource($result['member']),
            $message
        );
    }

    /**
     * Aprobar un registro público pendiente: activa la cuenta del alumno.
     */
    public function approve(string $member, ApproveMemberAction $action): JsonResponse
    {
        $model = $this->repository->find($member);
        if (! $model) {
            return ApiResponse::error('Miembro no encontrado.', 404);
        }

        $this->authorize('approve', $model);

        try {
            $result = $action->execute($model);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }

        return ApiResponse::success(new MemberResource($result), 'Registro aprobado. El alumno ya puede iniciar sesión.');
    }
}
