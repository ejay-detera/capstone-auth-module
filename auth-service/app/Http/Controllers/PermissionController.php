<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-roles');
        return Permission::all();
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-roles');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug',
            'description' => 'nullable|string|max:500',
        ]);

        $permission = Permission::create($validated);

        $this->logAudit($request, 'PERMISSION_CREATED', "Created permission: {$permission->name} ({$permission->slug})");

        return response()->json($permission, 201);
    }

    public function show($id)
    {
        Gate::authorize('manage-roles');
        return Permission::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('manage-roles');
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $permission->update($validated);

        // Invalidate cache for all affected users
        $this->invalidateAffectedUsers($permission);

        $this->logAudit($request, 'PERMISSION_UPDATED', "Updated permission: {$permission->name}");

        return response()->json($permission);
    }

    public function destroy(Request $request, $id)
    {
        Gate::authorize('manage-roles');
        $permission = Permission::findOrFail($id);

        // Invalidate cache before deletion
        $this->invalidateAffectedUsers($permission);

        $permission->delete();

        $this->logAudit($request, 'PERMISSION_DELETED', "Deleted permission: {$permission->name}");

        return response()->json(['message' => 'Permission deleted successfully.']);
    }

    public function getRoles($id)
    {
        Gate::authorize('manage-roles');
        $permission = Permission::findOrFail($id);
        return response()->json($permission->roles()->get(['roles.id', 'roles.name']));
    }

    public function syncRoles(Request $request, $id)
    {
        Gate::authorize('manage-roles');
        $permission = Permission::findOrFail($id);

        $request->validate([
            'role_ids' => 'present|array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        $permission->roles()->sync($request->role_ids);

        // Invalidate cache for all users who have this permission (either now or previously)
        // For simplicity, we can invalidate for all users in the affected roles
        $this->invalidateAffectedUsers($permission);

        $this->logAudit($request, 'PERMISSION_ROLES_UPDATED', "Updated roles for permission: {$permission->name}");

        return response()->json(['message' => 'Roles updated successfully.']);
    }

    private function invalidateAffectedUsers(Permission $permission)
    {
        $userIds = \App\Models\User::whereHas('profile.role.permissions', function ($query) use ($permission) {
            $query->where('permissions.id', $permission->id);
        })->pluck('id');

        foreach ($userIds as $userId) {
            \Illuminate\Support\Facades\Cache::store('database')->forget("permissions:user:{$userId}");
        }
    }

    private function logAudit(Request $request, $action, $description)
    {
        \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
            'actor_id' => $request->user()->id ?? null,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'action_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
