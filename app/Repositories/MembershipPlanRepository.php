<?php

namespace App\Repositories;

use App\Models\MembershipPlan;

class MembershipPlanRepository extends BaseRepository
{
    public function __construct(MembershipPlan $model)
    {
        parent::__construct($model);
    }

    public function findByMember(string $memberId): ?MembershipPlan
    {
        return $this->model->where('member_id', $memberId)->first();
    }

    public function create(array $data): MembershipPlan
    {
        return $this->model->create($data);
    }
}
