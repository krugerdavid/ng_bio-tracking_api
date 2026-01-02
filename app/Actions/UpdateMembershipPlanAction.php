<?php

namespace App\Actions;

use App\Repositories\MembershipPlanRepository;

class UpdateMembershipPlanAction implements Action
{
    public function __construct(private MembershipPlanRepository $repository) {}

    /**
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function execute(...$args): bool
    {
        [$id, $data] = $args;
        $plan = $this->repository->find($id);
        if (!$plan) return false;
        return $plan->update($data);
    }
}
