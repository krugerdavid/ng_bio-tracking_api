<?php

namespace App\Repositories;

use App\Models\Bioimpedance;
use Illuminate\Database\Eloquent\Collection;

class BioimpedanceRepository extends BaseRepository
{
    public function __construct(Bioimpedance $model)
    {
        parent::__construct($model);
    }

    public function findByMember(string $memberId): Collection
    {
        return $this->model->where('member_id', $memberId)->latest('date')->get();
    }

    public function create(array $data): Bioimpedance
    {
        return $this->model->create($data);
    }
}
