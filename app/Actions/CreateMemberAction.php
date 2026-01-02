<?php

namespace App\Actions;

use App\Models\Member;
use App\Repositories\MemberRepository;

class CreateMemberAction implements Action
{
    public function __construct(private MemberRepository $repository) {}

    /**
     * @param array $data
     * @return Member
     */
    public function execute(...$args): Member
    {
        [$data] = $args;
        return $this->repository->create($data);
    }
}
