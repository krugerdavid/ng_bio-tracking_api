<?php

namespace App\Actions;

use App\Models\Bioimpedance;
use App\Repositories\BioimpedanceRepository;

class RecordBioimpedanceAction implements Action
{
    public function __construct(private BioimpedanceRepository $repository) {}

    /**
     * @param array $data
     * @return Bioimpedance
     */
    public function execute(...$args): Bioimpedance
    {
        [$data] = $args;
        return $this->repository->create($data);
    }
}
