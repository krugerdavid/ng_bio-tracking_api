<?php

namespace App\Repositories;

use App\Models\Payment;
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

    public function create(array $data): Payment
    {
        return $this->model->create($data);
    }
}
