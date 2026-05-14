<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-roles');
        return Cache::remember('roles:list', 3600, function () {
            return Role::withCount('users')->get();
        });
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-roles');
        
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $role = Role::create($validated);
        Cache::forget('roles:list');

        $this->logAudit($request, 'ROLE_CREATED', "Created role: {$role->name}");

        return response()->json($role, 201);
    }

    public function show($id)
    {
        Gate::authorize('manage-roles');
        return Role::withCount('users')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('manage-roles');
        
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $role->update($validated);
        Cache::forget('roles:list');

        $this->logAudit($request, 'ROLE_UPDATED', "Updated role: {$role->name}");

        return response()->json($role);
    }

    public function destroy(Request $request, $id)
    {
        Gate::authorize('manage-roles');
        
        $role = Role::withCount('users')->findOrFail($id);

        if ($role->users_count > 0) {
            return response()->json([
                'message' => 'Cannot delete role with assigned users.',
                'user_count' => $role->users_count
            ], 409);
        }

        $roleName = $role->name;
        $role->delete();
        Cache::forget('roles:list');

        $this->logAudit($request, 'ROLE_DELETED', "Deleted role: {$roleName}");

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    public function users($id)
    {
        Gate::authorize('manage-roles');
        $role = Role::findOrFail($id);
        
        return User::whereHas('profile', function ($query) use ($id) {
            $query->where('role_id', $id);
        })->with(['profile.department'])->paginate(15);
    }

    public function assignRole(Request $request, $userId)
    {
        Gate::authorize('manage-roles');
        
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::findOrFail($userId);
        $role = Role::findOrFail($request->role_id);

        try {
            DB::beginTransaction();

            $profile = $user->profile;
            $oldRoleName = $profile->role ? $profile->role->name : 'None';
            
            $profile->update(['role_id' => $role->id]);

            // Invalidate permission cache
            Cache::forget("permissions:user:{$user->id}");

            $this->logAudit($request, 'ROLE_ASSIGNED', "Assigned role {$role->name} to user {$user->username} (was {$oldRoleName})");

            DB::commit();

            return response()->json([
                'message' => 'Role assigned successfully.',
                'user' => $user->load('profile.role')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to assign role.', 'error' => $e->getMessage()], 500);
        }
    }

    public function permissions($id)
    {
        Gate::authorize('manage-roles');
        $role = Role::findOrFail($id);
        return response()->json($role->permissions()->pluck('id'));
    }

    public function syncPermissions(Request $request, $id)
    {
        Gate::authorize('manage-roles');
        $role = Role::findOrFail($id);
        
        $request->validate([
            'permissions' => 'present|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role->permissions()->sync($request->permissions);
        
        // Invalidate permission cache for all users with this role
        $userIds = User::whereHas('profile', function($q) use ($id) {
            $q->where('role_id', $id);
        })->pluck('id');

        foreach ($userIds as $userId) {
            Cache::forget("permissions:user:{$userId}");
        }

        $this->logAudit($request, 'ROLE_PERMISSIONS_UPDATED', "Updated permissions for role: {$role->name}");

        return response()->json(['message' => 'Permissions updated successfully.']);
    }

    private function logAudit(Request $request, $action, $description)
    {
        DB::table('audit_logs')->insert([
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
