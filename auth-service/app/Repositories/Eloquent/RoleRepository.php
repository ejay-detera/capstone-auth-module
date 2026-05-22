<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleRepository implements RoleRepositoryInterface
{
    public function all(): array
    {
        return Role::withCount('users')->get()->toArray();
    }

    public function findById(int $id, array $relations = []): ?Role
    {
        return Role::withCount('users')->with($relations)->find($id);
    }

    public function findByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $role = Role::findOrFail($id);
        return $role->update($data);
    }

    public function delete(int $id): bool
    {
        $role = Role::findOrFail($id);
        return $role->delete();
    }

    public function getUsers(int $roleId, int $perPage = 15): LengthAwarePaginator
    {
        return User::whereHas('profile', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->with(['profile.department'])->paginate($perPage);
    }

    public function hasAssignedUsers(int $roleId): bool
    {
        $role = Role::withCount('users')->findOrFail($roleId);
        return $role->users_count > 0;
    }

    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $role = Role::findOrFail($roleId);
        $role->permissions()->sync($permissionIds);
    }

    public function getRolePermissions(int $roleId): array
    {
        $role = Role::findOrFail($roleId);
        return $role->permissions()->pluck('id')->toArray();
    }
}
