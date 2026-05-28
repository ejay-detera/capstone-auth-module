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
    protected InternalAuditService $internalAuditService;

    public function __construct(
        UserRepositoryInterface $userRepo,
        RoleRepositoryInterface $roleRepo,
        PermissionRepositoryInterface $permissionRepo,
        AuditLogRepositoryInterface $auditLogRepo,
        InternalAuditService $internalAuditService
    ) {
        $this->userRepo = $userRepo;
        $this->roleRepo = $roleRepo;
        $this->permissionRepo = $permissionRepo;
        $this->auditLogRepo = $auditLogRepo;
        $this->internalAuditService = $internalAuditService;
    }

    public function getRolesForUserCreation(?User $actor): array
    {
        try {
            $roles = Cache::remember('roles:all', 3600, function() {
                return Role::all()->toArray();
            });

            if ($actor && !in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
                if ($actor->profile?->department?->name === 'Finance' && $actor->profile?->role?->name === 'Admin') {
                    $allowed = ['Finance Manager', 'Finance Employee'];
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

    public function getRolesList(?User $actor = null): array
    {
        try {
            $roles = Cache::remember('roles:list', 3600, function () {
                return $this->roleRepo->all();
            });
        } catch (\Exception $e) {
            Log::warning('Cache unavailable for roles:list, querying DB directly', ['error' => $e->getMessage()]);
            $roles = $this->roleRepo->all();
        }

        if ($actor && !in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            if ($actor->profile?->department?->name === 'Finance' && $actor->profile?->role?->name === 'Admin') {
                $allowed = ['Finance Manager', 'Finance Employee'];
                $roles = array_filter($roles, function($r) use ($allowed) {
                    return in_array($r['name'], $allowed);
                });
                $roles = array_values($roles);
            } else {
                $roles = [];
            }
        }

        return $roles;
    }

    public function getPaginatedRoles(int $perPage = 15, ?string $search = null, ?User $actor = null): LengthAwarePaginator
    {
        $allowedNames = null;

        if ($actor && !in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            if ($actor->profile?->department?->name === 'Finance' && $actor->profile?->role?->name === 'Admin') {
                $allowedNames = ['Finance Manager', 'Finance Employee'];
            } else {
                // Return empty paginator
                return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
            }
        }

        return $this->roleRepo->paginate($perPage, $search, $allowedNames);
    }

    public function getPaginatedPermissions(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->permissionRepo->paginate($perPage, $search);
    }

    public function createRole(array $data, ?User $actor, string $ip, string $userAgent): Role
    {
        if ($actor && !in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to manage role configuration.'], 403)
            );
        }

        $role = $this->roleRepo->create($data);

        try { Cache::forget('roles:list'); } catch (\Exception $e) {}

        $this->auditLogRepo->log($actor ? $actor->id : null, 'ROLE_CREATED', "Created role: {$role->name}", $ip, $userAgent);

        $this->internalAuditService->pushEvent(
            'ROLE_CREATED',
            'Role',
            $role->id,
            ['message' => "Created role: {$role->name}", 'role_name' => $role->name],
            $actor
        );

        return $role;
    }

    public function getRole(int $id, ?User $actor = null): Role
    {
        $role = $this->roleRepo->findById($id);
        if (!$role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, $id);
        }
        if ($actor && !$this->isRoleAllowedForActor($role, $actor)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to access this role.'], 403)
            );
        }
        return $role;
    }

    public function updateRole(int $id, array $data, ?User $actor, string $ip, string $userAgent): Role
    {
        if ($actor && !in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to manage role configuration.'], 403)
            );
        }

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

        $this->internalAuditService->pushEvent(
            'ROLE_UPDATED',
            'Role',
            $role->id,
            ['message' => "Updated role '{$oldName}'", 'role_name' => $role->name],
            $actor
        );

        return $role;
    }

    public function deleteRole(int $id, ?User $actor, string $ip, string $userAgent): void
    {
        if ($actor && !in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to manage role configuration.'], 403)
            );
        }

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

        $this->internalAuditService->pushEvent(
            'ROLE_DELETED',
            'Role',
            $id,
            ['message' => "Deleted role: {$roleName}", 'role_name' => $roleName],
            $actor
        );
    }

    public function getRoleUsers(int $roleId, ?User $actor = null): LengthAwarePaginator
    {
        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, $roleId);
        }
        if ($actor && !$this->isRoleAllowedForActor($role, $actor)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to view users for this role.'], 403)
            );
        }

        if ($actor && $actor->profile?->role?->name === 'Admin' && $actor->profile?->department?->name === 'Finance') {
            return User::whereHas('profile', function ($query) use ($roleId, $actor) {
                $query->where('role_id', $roleId)
                      ->where('department_id', $actor->profile->department_id);
            })->with(['profile.department'])->paginate(15);
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

        if ($actor && !$this->isRoleAllowedForActor($role, $actor)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to assign this role.'], 403)
            );
        }

        if ($actor && $actor->profile?->role?->name === 'Admin' && $actor->profile?->department?->name === 'Finance') {
            if ($user->profile?->department_id != $actor->profile->department_id) {
                throw new HttpResponseException(
                    response()->json(['message' => 'You can only assign roles to users in the Finance department.'], 403)
                );
            }
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

            $this->internalAuditService->pushEvent(
                'ROLE_ASSIGNED',
                'User',
                $user->id,
                ['message' => "Assigned role {$role->name} to user {$user->email} (was {$oldRoleName})", 'role_name' => $role->name, 'email' => $user->email],
                $actor
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

    public function getRolePermissions(int $roleId, ?User $actor = null): array
    {
        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, $roleId);
        }
        if ($actor && !$this->isRoleAllowedForActor($role, $actor)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to view permissions for this role.'], 403)
            );
        }
        return $this->roleRepo->getRolePermissions($roleId);
    }

    public function syncRolePermissions(int $roleId, array $permissionIds, ?User $actor, string $ip, string $userAgent): void
    {
        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, $roleId);
        }

        if ($actor && !$this->isRoleAllowedForActor($role, $actor)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized to modify permissions for this role.'], 403)
            );
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

        $this->internalAuditService->pushEvent(
            'ROLE_PERMISSIONS_UPDATED',
            'Role',
            $roleId,
            ['message' => "Updated permissions for role: {$role->name}", 'role_name' => $role->name],
            $actor
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

    public function getPermissionRoles(int $permissionId, ?User $actor = null): Collection
    {
        $permission = $this->permissionRepo->findById($permissionId);
        if (!$permission) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Permission::class, $permissionId);
        }

        $roles = $this->permissionRepo->getRoles($permissionId);

        if ($actor && !in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            if ($actor->profile?->role?->name === 'Admin' && $actor->profile?->department?->name === 'Finance') {
                $allowedNames = ['Finance Manager', 'Finance Employee'];
                $roles = $roles->filter(function($role) use ($allowedNames) {
                    return in_array($role->name, $allowedNames);
                });
            } else {
                return new Collection();
            }
        }

        return $roles;
    }

    public function syncPermissionRoles(int $permissionId, array $roleIds, ?User $actor, string $ip, string $userAgent): void
    {
        $permission = $this->permissionRepo->findById($permissionId);
        if (!$permission) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Permission::class, $permissionId);
        }

        if ($actor && !in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            if ($actor->profile?->role?->name === 'Admin' && $actor->profile?->department?->name === 'Finance') {
                // Get roles currently associated with the permission
                $currentRoleIds = $permission->roles()->pluck('roles.id')->toArray();

                // Allowed roles for Finance Admin
                $allowedRoles = Role::whereIn('name', ['Finance Manager', 'Finance Employee'])->pluck('id')->toArray();

                $disallowedRequested = array_diff($roleIds, $allowedRoles);
                $disallowedCurrent = array_diff($currentRoleIds, $allowedRoles);

                sort($disallowedRequested);
                sort($disallowedCurrent);
                if ($disallowedRequested !== $disallowedCurrent) {
                    throw new HttpResponseException(
                        response()->json(['message' => 'You are only authorized to map permissions to Finance Manager or Finance Employee roles.'], 403)
                    );
                }
            } else {
                throw new HttpResponseException(
                    response()->json(['message' => 'Unauthorized to modify roles for permissions.'], 403)
                );
            }
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

        $this->internalAuditService->pushEvent(
            'PERMISSION_ROLES_UPDATED',
            'Permission',
            $permissionId,
            ['message' => "Updated roles for permission: {$permission->name}", 'permission_name' => $permission->name],
            $actor
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

    protected function isRoleAllowedForActor(Role|int $role, ?User $actor): bool
    {
        if (!$actor) {
            return false;
        }

        $actorRole = $actor->profile?->role?->name;
        $actorDept = $actor->profile?->department?->name;

        if (in_array($actorRole, ['IT Admin', 'Super Admin'])) {
            return true;
        }

        if ($actorRole === 'Admin' && $actorDept === 'Finance') {
            $roleName = $role instanceof Role ? $role->name : $this->roleRepo->findById($role)?->name;
            return in_array($roleName, ['Finance Manager', 'Finance Employee']);
        }

        return false;
    }
}
