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
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'date' => 'required|date',
            'height' => 'required|numeric',
            'weight' => 'required|numeric',
            'imc' => 'required|numeric',
            'body_fat_percentage' => 'required|numeric',
            'muscle_mass_percentage' => 'required|numeric',
            'kcal' => 'required|numeric',
            'metabolic_age' => 'required|numeric',
            'visceral_fat_percentage' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $member = Member::find($validated['member_id']);
        $this->authorize('view', $member);

        $record = $action->execute($validated);

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

        $validated = $request->validate([
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
        ]);

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
