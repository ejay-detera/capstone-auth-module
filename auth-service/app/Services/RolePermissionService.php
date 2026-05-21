<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Exceptions\HttpResponseException;

class RolePermissionService
{
    protected UserRepositoryInterface $userRepo;
    protected RoleRepositoryInterface $roleRepo;
    protected PermissionRepositoryInterface $permissionRepo;
    protected AuditLogRepositoryInterface $auditLogRepo;

    public function __construct(
        UserRepositoryInterface $userRepo,
        RoleRepositoryInterface $roleRepo,
        PermissionRepositoryInterface $permissionRepo,
        AuditLogRepositoryInterface $auditLogRepo
    ) {
        $this->userRepo = $userRepo;
        $this->roleRepo = $roleRepo;
        $this->permissionRepo = $permissionRepo;
        $this->auditLogRepo = $auditLogRepo;
    }

    public function getRolesForUserCreation(?User $actor): array
    {
        try {
            $roles = Cache::remember('roles:all', 3600, function() {
                return Role::all()->toArray();
            });

            if ($actor && !in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
                if ($actor->profile?->department?->name === 'Finance' && $actor->profile?->role?->name === 'Admin') {
                    $allowed = ['Manager', 'Employee'];
                    $roles = array_filter($roles, function($r) use ($allowed) {
                        return in_array($r['name'], $allowed);
                    });
                    $roles = array_values($roles);
                } else {
                    $roles = [];
                }
            }

            return $roles;
        } catch (\Exception $e) {
            return Role::all()->toArray();
        }
    }

    public function getRolesList(): array
    {
        try {
            return Cache::remember('roles:list', 3600, function () {
                return $this->roleRepo->all();
            });
        } catch (\Exception $e) {
            Log::warning('Cache unavailable for roles:list, querying DB directly', ['error' => $e->getMessage()]);
            return $this->roleRepo->all();
        }
    }

    public function createRole(array $data, ?User $actor, string $ip, string $userAgent): Role
    {
        $role = $this->roleRepo->create($data);

        try { Cache::forget('roles:list'); } catch (\Exception $e) {}

        $this->auditLogRepo->log($actor ? $actor->id : null, 'ROLE_CREATED', "Created role: {$role->name}", $ip, $userAgent);

        return $role;
    }

    public function getRole(int $id): Role
    {
        $role = $this->roleRepo->findById($id);
        if (!$role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, $id);
        }
        return $role;
    }

    public function updateRole(int $id, array $data, ?User $actor, string $ip, string $userAgent): Role
    {
        $role = $this->roleRepo->findById($id);
        if (!$role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, $id);
        }

        $oldName = $role->name;
        $oldDesc = $role->description;

        $this->roleRepo->update($id, $data);

        try { Cache::forget('roles:list'); } catch (\Exception $e) {}

        $role->refresh();

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'ROLE_UPDATED',
            "Updated role '{$oldName}'. Changes: Name[{$oldName} -> {$role->name}], Description[{$oldDesc} -> {$role->description}]",
            $ip,
            $userAgent
        );

        return $role;
    }

    public function deleteRole(int $id, ?User $actor, string $ip, string $userAgent): void
    {
        if ($this->roleRepo->hasAssignedUsers($id)) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Cannot delete role with assigned users.',
                    'user_count' => $this->roleRepo->findById($id)->users_count
                ], 409)
            );
        }

        $role = $this->roleRepo->findById($id);
        $roleName = $role->name;
        $this->roleRepo->delete($id);

        try { Cache::forget('roles:list'); } catch (\Exception $e) {}

        $this->auditLogRepo->log($actor ? $actor->id : null, 'ROLE_DELETED', "Deleted role: {$roleName}", $ip, $userAgent);
    }

    public function getRoleUsers(int $roleId): LengthAwarePaginator
    {
        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, $roleId);
        }
        return $this->roleRepo->getUsers($roleId);
    }

    public function assignRole(int $userId, int $roleId, ?User $actor, string $ip, string $userAgent): User
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(User::class, $userId);
        }

        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, $roleId);
        }

        try {
            DB::beginTransaction();

            $profile = $user->profile;
            $oldRoleName = $profile->role ? $profile->role->name : 'None';

            $profile->update(['role_id' => $role->id]);

            // Invalidate permission cache
            $this->invalidateUserPermissionCache($user->id);

            $this->auditLogRepo->log(
                $actor ? $actor->id : null,
                'ROLE_ASSIGNED',
                "Assigned role {$role->name} to user {$user->email} (was {$oldRoleName})",
                $ip,
                $userAgent
            );

            DB::commit();

            return $user->load('profile.role');

        } catch (\Exception $e) {
            DB::rollBack();
            throw new HttpResponseException(
                response()->json(['message' => 'Failed to assign role.', 'error' => $e->getMessage()], 500)
            );
        }
    }

    public function getRolePermissions(int $roleId): array
    {
        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, $roleId);
        }
        return $this->roleRepo->getRolePermissions($roleId);
    }

    public function syncRolePermissions(int $roleId, array $permissionIds, ?User $actor, string $ip, string $userAgent): void
    {
        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, $roleId);
        }

        $this->roleRepo->syncPermissions($roleId, $permissionIds);

        // Invalidate permission cache for all users with this role
        $userIds = User::whereHas('profile', function($q) use ($roleId) {
            $q->where('role_id', $roleId);
        })->pluck('id');

        foreach ($userIds as $userId) {
            $this->invalidateUserPermissionCache($userId);
        }

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'ROLE_PERMISSIONS_UPDATED',
            "Updated permissions for role: {$role->name}",
            $ip,
            $userAgent
        );
    }

    public function getUserPermissions(int $userId): array
    {
        try {
            return Cache::store('database')->remember("permissions:user:{$userId}", 300, function () use ($userId) {
                return $this->userRepo->getUserPermissions($userId);
            });
        } catch (\Exception $e) {
            Log::warning('Cache unavailable for user permissions, querying DB directly', ['error' => $e->getMessage()]);
            return $this->userRepo->getUserPermissions($userId);
        }
    }

    public function getUserPermissionsBySystem(int $userId, ?string $system = null): array
    {
        if ($system) {
            $user = $this->userRepo->findById($userId, ['profile.role']);
            if (!$user || !$user->profile || !$user->profile->role) {
                return [];
            }
            return $user->profile->role->permissions()
                ->where('system', $system)
                ->pluck('slug')
                ->toArray();
        }
        return $this->getUserPermissions($userId);
    }

    public function getAllPermissions(): Collection
    {
        return $this->permissionRepo->all();
    }

    public function createPermission(array $data, ?User $actor, string $ip, string $userAgent): Permission
    {
        $permission = $this->permissionRepo->create($data);

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'PERMISSION_CREATED',
            "Created permission: {$permission->name} ({$permission->slug})",
            $ip,
            $userAgent
        );

        return $permission;
    }

    public function getPermission(int $id): Permission
    {
        $permission = $this->permissionRepo->findById($id);
        if (!$permission) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Permission::class, $id);
        }
        return $permission;
    }

    public function updatePermission(int $id, array $data, ?User $actor, string $ip, string $userAgent): Permission
    {
        $permission = $this->permissionRepo->findById($id);
        if (!$permission) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Permission::class, $id);
        }

        $this->permissionRepo->update($id, $data);

        $permission->refresh();

        // Invalidate cache for all affected users
        $this->invalidateAffectedUsers($permission);

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'PERMISSION_UPDATED',
            "Updated permission: {$permission->name}",
            $ip,
            $userAgent
        );

        return $permission;
    }

    public function deletePermission(int $id, ?User $actor, string $ip, string $userAgent): void
    {
        $permission = $this->permissionRepo->findById($id);
        if (!$permission) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Permission::class, $id);
        }

        // Invalidate cache before deletion
        $this->invalidateAffectedUsers($permission);

        $permissionName = $permission->name;
        $this->permissionRepo->delete($id);

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'PERMISSION_DELETED',
            "Deleted permission: {$permissionName}",
            $ip,
            $userAgent
        );
    }

    public function getPermissionRoles(int $permissionId): Collection
    {
        $permission = $this->permissionRepo->findById($permissionId);
        if (!$permission) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Permission::class, $permissionId);
        }
        return $this->permissionRepo->getRoles($permissionId);
    }

    public function syncPermissionRoles(int $permissionId, array $roleIds, ?User $actor, string $ip, string $userAgent): void
    {
        $permission = $this->permissionRepo->findById($permissionId);
        if (!$permission) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Permission::class, $permissionId);
        }

        $this->permissionRepo->syncRoles($permissionId, $roleIds);

        // Invalidate cache for all users who have this permission
        $this->invalidateAffectedUsers($permission);

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'PERMISSION_ROLES_UPDATED',
            "Updated roles for permission: {$permission->name}",
            $ip,
            $userAgent
        );
    }

    private function invalidateAffectedUsers(Permission $permission): void
    {
        try {
            $userIds = $this->permissionRepo->getUsersWithPermission($permission->id);
            foreach ($userIds as $userId) {
                $this->invalidateUserPermissionCache($userId);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate user permission cache', ['error' => $e->getMessage()]);
        }
    }

    private function invalidateUserPermissionCache(int $userId): void
    {
        try {
            Cache::store('database')->forget("permissions:user:{$userId}");
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate permission cache', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
