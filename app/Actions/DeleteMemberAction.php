<?php

namespace App\Actions;

use App\Repositories\MemberRepository;

class DeleteMemberAction implements Action
{
    public function __construct(private MemberRepository $repository) {}

    /**
     * @param string $id
     * @return bool
     */
    public function execute(...$args): bool
    {
        [$id] = $args;
        return $this->repository->delete($id);
    }
}
