<?php

namespace App\Http\Controllers\Api;

use App\Actions\LoginAction;
use App\Actions\LogoutAction;
use App\Http\Controllers\Controller;
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
}
