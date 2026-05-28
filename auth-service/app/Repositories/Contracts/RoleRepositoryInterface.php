<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RoleRepositoryInterface
{
    public function all(): array;

    public function paginate(int $perPage = 15, ?string $search = null, ?array $allowedNames = null): LengthAwarePaginator;


    public function findById(int $id, array $relations = []): ?Role;

    public function findByName(string $name): ?Role;

    public function create(array $data): Role;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function getUsers(int $roleId, int $perPage = 15): LengthAwarePaginator;

    public function hasAssignedUsers(int $roleId): bool;

    public function syncPermissions(int $roleId, array $permissionIds): void;

    public function getRolePermissions(int $roleId): array;
}
