<?php

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Models\User;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function all(): Collection
    {
        return Permission::all();
    }

    public function findById(int $id): ?Permission
    {
        return Permission::find($id);
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $permission = Permission::findOrFail($id);
        return $permission->update($data);
    }

    public function delete(int $id): bool
    {
        $permission = Permission::findOrFail($id);
        return $permission->delete();
    }

    public function getRoles(int $permissionId): Collection
    {
        $permission = Permission::findOrFail($permissionId);
        return $permission->roles()->get(['roles.id', 'roles.name']);
    }

    public function syncRoles(int $permissionId, array $roleIds): void
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->roles()->sync($roleIds);
    }

    public function getUsersWithPermission(int $permissionId): array
    {
        return User::whereHas('profile.role.permissions', function ($query) use ($permissionId) {
            $query->where('permissions.id', $permissionId);
        })->pluck('id')->toArray();
    }
}
