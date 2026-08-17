<?php

namespace App\Http\Controllers\Api;

use App\Actions\AcceptMemberInviteAction;
use App\Actions\LoginAction;
use App\Actions\LogoutAction;
use App\Actions\RegisterMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcceptMemberInviteRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, LoginAction $loginAction): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $result = $loginAction->execute($request->email, $request->password);
            
            return ApiResponse::success([
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ], 'Login exitoso.');
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }
    }

    public function logout(Request $request, LogoutAction $logoutAction): JsonResponse
    {
        $logoutAction->execute($request->user());
        return ApiResponse::success(null, 'Logout exitoso.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('member');
        return ApiResponse::success(new UserResource($user));
    }

    /**
     * Auto-registro público de un alumno: queda pendiente de aprobación del profe.
     */
    public function register(RegisterRequest $request, RegisterMemberAction $action): JsonResponse
    {
        $action->execute($request->validated());

        return ApiResponse::success(
            null,
            'Tu registro fue enviado. El profe va a revisar tu solicitud y te avisará cuando puedas ingresar.',
            201
        );
    }

    /**
     * Aceptar invitación de miembro: setea contraseña con el token del email.
     */
    public function acceptInvite(AcceptMemberInviteRequest $request, AcceptMemberInviteAction $action): JsonResponse
    {
        try {
            $user = $action->execute($request->validated());
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }

        return ApiResponse::success(
            new UserResource($user),
            'Acceso activado. Ya podés iniciar sesión.'
        );
    }
}
