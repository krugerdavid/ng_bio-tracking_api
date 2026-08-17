<?php

namespace App\Http\Controllers\Api;

use App\Actions\RecordBioimpedanceAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\BioimpedanceResource;
use App\Models\Member;
use App\Repositories\BioimpedanceRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BioimpedanceController extends Controller
{
    public function __construct(private BioimpedanceRepository $repository) {}

    public function index(Request $request, string $memberId): JsonResponse
    {
        $member = Member::find($memberId);
        if (! $member) {
            return ApiResponse::error('Miembro no encontrado.', 404);
        }
        $this->authorize('view', $member);

        $records = $this->repository->findByMember($memberId);
        return ApiResponse::success(BioimpedanceResource::collection($records));
    }

    public function store(Request $request, RecordBioimpedanceAction $action): JsonResponse
    {
        $user = $request->user();

        $rules = [
            'date' => 'required|date',
            'weight' => 'required|numeric',
            'height' => 'nullable|numeric',
            'imc' => 'nullable|numeric',
            'body_fat_percentage' => 'nullable|numeric',
            'muscle_mass_percentage' => 'nullable|numeric',
            'kcal' => 'nullable|numeric',
            'metabolic_age' => 'nullable|numeric',
            'visceral_fat_percentage' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ];
        if ($user->canAccessAllMembers()) {
            $rules['member_id'] = 'required|exists:members,id';
        }
        $validated = $request->validate($rules);

        $this->authorize('create', \App\Models\Bioimpedance::class);

        if ($user->canAccessAllMembers()) {
            $member = Member::find($validated['member_id']);
            if (! $member) {
                return ApiResponse::error('Miembro no encontrado.', 404);
            }
        } elseif (! $user->member) {
            return ApiResponse::error('Tu cuenta no tiene una ficha de alumno vinculada.', 422);
        }

        $record = $action->execute($validated, $user);

        return ApiResponse::success(new BioimpedanceResource($record), 'Registro de bioimpedancia guardado.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $record = $this->repository->find($id);

        if (! $record) {
            return ApiResponse::error('Registro no encontrado.', 404);
        }
        $this->authorize('view', $record);

        return ApiResponse::success(new BioimpedanceResource($record));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $record = $this->repository->find($id);
        if (! $record) {
            return ApiResponse::error('Registro no encontrado.', 404);
        }
        $this->authorize('update', $record);

        $rules = [
            'date' => 'sometimes|date',
            'height' => 'sometimes|numeric',
            'weight' => 'sometimes|numeric',
            'imc' => 'sometimes|numeric',
            'body_fat_percentage' => 'sometimes|numeric',
            'muscle_mass_percentage' => 'sometimes|numeric',
            'kcal' => 'sometimes|numeric',
            'metabolic_age' => 'sometimes|numeric',
            'visceral_fat_percentage' => 'sometimes|numeric',
            'notes' => 'nullable|string',
        ];
        if ($request->user()->canAccessAllMembers()) {
            $rules['status'] = 'sometimes|string|in:pending,confirmed';
        }
        $validated = $request->validate($rules);

        $success = $this->repository->update($id, $validated);

        if (!$success) {
            return ApiResponse::error('No se pudo actualizar el registro.', 400);
        }

        return ApiResponse::success(new BioimpedanceResource($this->repository->find($id)), 'Registro actualizado exitosamente.');
    }

    public function destroy(string $id): JsonResponse
    {
        $record = $this->repository->find($id);
        if (! $record) {
            return ApiResponse::error('Registro no encontrado.', 404);
        }
        $this->authorize('delete', $record);

        $success = $this->repository->delete($id);

        if (! $success) {
            return ApiResponse::error('No se pudo eliminar el registro.', 400);
        }

        return ApiResponse::success(null, 'Registro eliminado exitosamente.');
    }
}
