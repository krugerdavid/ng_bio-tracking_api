<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserRepository $repository) {}

    /**
     * List users (root and admin). Returns a plain array for the frontend.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\User::class);

        $users = $this->repository->all();

        return ApiResponse::success(
            UserResource::collection($users)->resolve(),
            'Usuarios recuperados.'
        );
    }

    /**
     * Create a user. Only root can create admin; root and admin can create member.
     */
    public function store(Request $request, CreateUserAction $action): JsonResponse
    {
        $this->authorize('create', \App\Models\User::class);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:admin,member,user',
        ]);

        $role = $validated['role'] === 'user' ? 'member' : $validated['role'];
        if ($role === 'admin' && ! $request->user()->isRoot()) {
            return ApiResponse::error('Solo root puede crear usuarios administrador.', 403);
        }
        $validated['role'] = $role;
        $validated['name'] = $validated['name'] ?? explode('@', $validated['email'])[0];

        $user = $action->execute($validated);

        return ApiResponse::success(new UserResource($user), 'Usuario creado.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $user = $this->repository->find((int) $id);
        if (! $user) {
            return ApiResponse::error('Usuario no encontrado.', 404);
        }
        $this->authorize('view', $user);

        return ApiResponse::success(new UserResource($user));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = $this->repository->find((int) $id);
        if (! $user) {
            return ApiResponse::error('Usuario no encontrado.', 404);
        }
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|string|in:admin,member,user',
        ]);
        if (isset($validated['role']) && $validated['role'] === 'user') {
            $validated['role'] = 'member';
        }

        if (isset($validated['password']) && $validated['password']) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $this->repository->update((int) $id, $validated);

        return ApiResponse::success(
            new UserResource($this->repository->find((int) $id)),
            'Usuario actualizado.'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $user = $this->repository->find((int) $id);
        if (! $user) {
            return ApiResponse::error('Usuario no encontrado.', 404);
        }
        $this->authorize('delete', $user);

        $user->delete();

        return ApiResponse::success(null, 'Usuario eliminado.');
    }
}
