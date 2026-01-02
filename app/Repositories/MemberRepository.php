<?php

namespace App\Repositories;

use App\Models\Member;
use Illuminate\Pagination\LengthAwarePaginator;

class MemberRepository extends BaseRepository
{
    public function __construct(Member $model)
    {
        parent::__construct($model);
    }

    public function search(?string $query, int $perPage = 15): LengthAwarePaginator
    {
        $builder = $this->model->newQuery();

        if ($query) {
            $builder->where('name', 'ilike', "%{$query}%")
                    ->orWhere('document_number', 'ilike', "%{$query}%");
        }

        return $builder->latest()->paginate($perPage);
    }

    public function create(array $data): Member
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): bool
    {
        $member = $this->find($id);
        if (!$member) return false;
        return $member->update($data);
    }
}
