<?php

namespace App\Repositories;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository extends BaseRepository
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    public function findByMember(string $memberId): Collection
    {
        return $this->model->where('member_id', $memberId)->latest('month')->get();
    }

    public function findByMonth(string $month): Collection
    {
        return $this->model->where('month', $month)->get();
    }

    /**
     * List payments globally, latest first (for dashboard "últimos pagos").
     */
    public function listLatest(int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->latest('created_at')->paginate($perPage);
    }

    public function create(array $data): Payment
    {
        return $this->model->create($data);
    }
}
