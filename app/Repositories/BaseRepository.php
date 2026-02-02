<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function find(string $id): ?Model
    {
        return $this->model->find($id);
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function delete(string $id): bool
    {
        return $this->model->destroy($id) > 0;
    }

    public function update(string $id, array $data): bool
    {
        $record = $this->find($id);
        if (!$record) return false;
        return $record->update($data);
    }
}
