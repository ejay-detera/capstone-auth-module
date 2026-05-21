<?php

namespace App\Repositories\Contracts;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

interface PermissionRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Permission;

    public function create(array $data): Permission;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function getRoles(int $permissionId): Collection;

    public function syncRoles(int $permissionId, array $roleIds): void;

    public function getUsersWithPermission(int $permissionId): array;
}
