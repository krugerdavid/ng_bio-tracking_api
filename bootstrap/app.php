<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Respuestas de API en español
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson() && ! str_starts_with($request->path(), 'api/')) {
                return null;
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            if ($e instanceof \RuntimeException && str_contains($e->getMessage(), 'Bcrypt algorithm')) {
                return response()->json([
                    'message' => 'La contraseña no está almacenada con el algoritmo correcto. Contacte al administrador.',
                    'errors' => ['email' => ['Las credenciales no son válidas.']],
                ], 422);
            }

            return null;
        });
    })->create();
