<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()->with('user');

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->string('auditable_type'));
        }
        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', $request->string('auditable_id'));
        }
        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->string('to'));
        }

        $perPage = max(1, min(100, (int) $request->query('page_size', 15)));
        $logs = $query->latest()->paginate($perPage);

        return ApiResponse::success(
            AuditLogResource::collection($logs)->response()->getData(true),
            'Registro de auditoría recuperado.'
        );
    }

    public function show(string $id): JsonResponse
    {
        $log = AuditLog::with('user', 'auditable')->find($id);

        if (! $log) {
            return ApiResponse::error('Entrada de auditoría no encontrada.', 404);
        }
        $this->authorize('view', $log);

        return ApiResponse::success(new AuditLogResource($log));
    }
}
