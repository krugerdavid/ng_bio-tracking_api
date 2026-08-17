<?php

namespace App\Repositories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class MemberRepository extends BaseRepository
{
    public function __construct(Member $model)
    {
        parent::__construct($model);
    }

    /**
     * Search members scoped by user role: root/admin see all, member sees only their own.
     */
    public function searchForUser(
        User $user,
        ?string $query,
        int $perPage = 15,
        ?string $trainingGroup = null,
        ?string $status = null
    ): LengthAwarePaginator {
        $builder = $this->model->newQuery()->with('user');

        $like = '%' . addcslashes($query ?? '', '%_\\') . '%';
        $applyFilters = function ($builder) use ($like, $query, $trainingGroup, $status) {
            if ($query !== null && $query !== '') {
                $builder->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('document_number', 'like', $like);
                });
            }
            if ($trainingGroup !== null && $trainingGroup !== '') {
                $builder->where('training_group', $trainingGroup);
            }
            if ($status !== null && $status !== '') {
                $builder->whereHas('user', fn ($q) => $q->where('status', $status));
            }
        };

        if ($user->canAccessAllMembers()) {
            $applyFilters($builder);
            return $builder->latest()->paginate($perPage);
        }

        // Member role: only their linked member
        $member = $user->member;
        if (! $member) {
            return $this->model->newQuery()->whereRaw('1 = 0')->paginate($perPage);
        }
        $builder->where('id', $member->id);
        $applyFilters($builder);
        return $builder->latest()->paginate($perPage);
    }

    public function search(?string $query, int $perPage = 15): LengthAwarePaginator
    {
        $builder = $this->model->newQuery();

        if ($query !== null && $query !== '') {
            $like = '%' . addcslashes($query, '%_\\') . '%';
            $builder->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('document_number', 'like', $like);
            });
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
