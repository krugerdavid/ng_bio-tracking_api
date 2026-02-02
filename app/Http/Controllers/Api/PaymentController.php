<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreatePaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\PaymentResource;
use App\Repositories\PaymentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentRepository $repository) {}

    public function index(string $memberId): JsonResponse
    {
        $payments = $this->repository->findByMember($memberId);
        return ApiResponse::success(PaymentResource::collection($payments));
    }

    public function store(Request $request, CreatePaymentAction $action): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'amount' => 'required|numeric',
            'payment_date' => 'required|date',
            'status' => 'required|string|in:paid,pending,overdue',
            'notes' => 'nullable|string',
        ]);

        $payment = $action->execute($validated);

        return ApiResponse::success(new PaymentResource($payment), 'Pago registrado exitosamente.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $payment = $this->repository->find($id);

        if (!$payment) {
            return ApiResponse::error('Pago no encontrado.', 404);
        }

        return ApiResponse::success(new PaymentResource($payment));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'sometimes|string|regex:/^\d{4}-\d{2}$/',
            'amount' => 'sometimes|numeric',
            'payment_date' => 'sometimes|date',
            'status' => 'sometimes|string|in:paid,pending,overdue',
            'notes' => 'nullable|string',
        ]);

        $success = $this->repository->update($id, $validated);

        if (!$success) {
            return ApiResponse::error('No se pudo actualizar el pago.', 400);
        }

        return ApiResponse::success(new PaymentResource($this->repository->find($id)), 'Pago actualizado exitosamente.');
    }

    public function destroy(string $id): JsonResponse
    {
        $success = $this->repository->delete($id);

        if (!$success) {
            return ApiResponse::error('No se pudo eliminar el pago.', 400);
        }

        return ApiResponse::success(null, 'Pago eliminado exitosamente.');
    }
}
