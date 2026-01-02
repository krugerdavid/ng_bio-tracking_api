<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateMemberAction;
use App\Actions\DeleteMemberAction;
use App\Actions\UpdateMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\MemberResource;
use App\Repositories\MemberRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(private MemberRepository $repository) {}

    public function index(Request $request): JsonResponse
    {
        $members = $this->repository->search(
            $request->query('search'),
            $request->query('page_size', 15)
        );

        return ApiResponse::success(
            MemberResource::collection($members)->response()->getData(true),
            'Miembros recuperados exitosamente.'
        );
    }

    public function store(Request $request, CreateMemberAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_number' => 'nullable|string|unique:members,document_number',
            'email' => 'nullable|email|unique:members,email',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
        ]);

        $member = $action->execute($validated);

        return ApiResponse::success(new MemberResource($member), 'Miembro creado exitosamente.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $member = $this->repository->find($id);

        if (!$member) {
            return ApiResponse::error('Miembro no encontrado.', 404);
        }

        $member->load('membershipPlan');

        return ApiResponse::success(new MemberResource($member));
    }

    public function update(Request $request, string $id, UpdateMemberAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'document_number' => 'nullable|string|unique:members,document_number,' . $id,
            'email' => 'nullable|email|unique:members,email,' . $id,
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
        ]);

        $success = $action->execute($id, $validated);

        if (!$success) {
            return ApiResponse::error('No se pudo actualizar el miembro.', 400);
        }

        return ApiResponse::success(new MemberResource($this->repository->find($id)), 'Miembro actualizado exitosamente.');
    }

    public function destroy(string $id, DeleteMemberAction $action): JsonResponse
    {
        $success = $action->execute($id);

        if (!$success) {
            return ApiResponse::error('No se pudo eliminar el miembro.', 400);
        }

        return ApiResponse::success(null, 'Miembro eliminado exitosamente.');
    }
}
