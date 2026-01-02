<?php

namespace App\Actions;

use App\Repositories\MemberRepository;

class UpdateMemberAction implements Action
{
    public function __construct(private MemberRepository $repository) {}

    /**
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function execute(...$args): bool
    {
        [$id, $data] = $args;
        return $this->repository->update($id, $data);
    }
}
