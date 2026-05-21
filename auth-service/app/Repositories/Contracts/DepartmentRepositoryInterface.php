<?php

namespace App\Repositories\Contracts;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DepartmentRepositoryInterface
{
    public function all(): array;

    public function findById(int $id, array $relations = []): ?Department;

    public function findByName(string $name): ?Department;

    public function create(array $data): Department;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function getUsers(int $departmentId, int $perPage = 15): LengthAwarePaginator;

    public function hasAssignedUsers(int $departmentId): bool;
}
