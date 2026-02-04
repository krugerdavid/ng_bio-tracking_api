<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BioimpedanceController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MembershipPlanController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Users (solo root puede listar/crear/editar/eliminar)
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

    // Members
    Route::get('members/{memberId}/debt', [MemberController::class, 'debtSummary']);
    Route::apiResource('members', MemberController::class);

    // Bioimpedance
    Route::get('members/{memberId}/bioimpedance', [BioimpedanceController::class, 'index']);
    Route::get('bioimpedances/{id}', [BioimpedanceController::class, 'show']);
    Route::post('bioimpedances', [BioimpedanceController::class, 'store']);
    Route::put('bioimpedances/{id}', [BioimpedanceController::class, 'update']);
    Route::delete('bioimpedances/{id}', [BioimpedanceController::class, 'destroy']);

    // Payments
    Route::get('payments', [PaymentController::class, 'list']);
    Route::get('members/{memberId}/payments', [PaymentController::class, 'index']);
    Route::get('payments/{id}', [PaymentController::class, 'show']);
    Route::post('payments', [PaymentController::class, 'store']);
    Route::put('payments/{id}', [PaymentController::class, 'update']);
    Route::delete('payments/{id}', [PaymentController::class, 'destroy']);

    // Membership Plans
    Route::get('members/{memberId}/plan', [MembershipPlanController::class, 'showByMember']);
    Route::post('plans', [MembershipPlanController::class, 'store']);
    Route::put('plans/{id}', [MembershipPlanController::class, 'update']);

    // Audit log (read-only)
    Route::get('audit-logs', [AuditLogController::class, 'index']);
    Route::get('audit-logs/{id}', [AuditLogController::class, 'show']);
});
