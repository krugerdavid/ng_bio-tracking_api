<?php

namespace App\Actions;

use App\Enums\BioimpedanceStatus;
use App\Models\Bioimpedance;
use App\Models\User;
use App\Repositories\BioimpedanceRepository;

class RecordBioimpedanceAction implements Action
{
    public function __construct(private BioimpedanceRepository $repository) {}

    /**
     * @param array $data
     * @param User $user
     * @return Bioimpedance
     */
    public function execute(...$args): Bioimpedance
    {
        [$data, $user] = $args;

        if ($user->canAccessAllMembers()) {
            $data['status'] = BioimpedanceStatus::Confirmed->value;
        } else {
            // Autocarga de un alumno: ignora cualquier member_id/status del body.
            $data['member_id'] = $user->member->id;
            $data['status'] = BioimpedanceStatus::Pending->value;
        }

        return $this->repository->create($data);
    }
}
