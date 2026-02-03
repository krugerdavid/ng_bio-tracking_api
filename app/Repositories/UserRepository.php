<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public function __construct(private User $model) {}

    public function find(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function all(): Collection
    {
        return $this->model->orderBy('id')->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->orderBy('id')->paginate($perPage);
    }

    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = $this->find($id);
        if (! $user) {
            return false;
        }
        return $user->update($data);
    }
}
