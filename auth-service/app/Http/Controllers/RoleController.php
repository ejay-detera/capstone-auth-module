<?php

namespace App\Http\Controllers;

use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    protected RolePermissionService $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    public function index()
    {
        Gate::authorize('manage-roles');
        return response()->json($this->rolePermissionService->getRolesList());
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-roles');

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $role = $this->rolePermissionService->createRole(
            $validated,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($role, 201);
    }

    public function show($id)
    {
        Gate::authorize('manage-roles');
        return response()->json($this->rolePermissionService->getRole((int) $id));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('manage-roles');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $role = $this->rolePermissionService->updateRole(
            (int) $id,
            $validated,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($role);
    }

    public function destroy(Request $request, $id)
    {
        Gate::authorize('manage-roles');

        $this->rolePermissionService->deleteRole(
            (int) $id,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    public function users($id)
    {
        Gate::authorize('manage-roles');
        return response()->json($this->rolePermissionService->getRoleUsers((int) $id));
    }

    public function assignRole(Request $request, $userId)
    {
        Gate::authorize('manage-roles');

        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = $this->rolePermissionService->assignRole(
            (int) $userId,
            (int) $request->role_id,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Role assigned successfully.',
            'user' => $user
        ]);
    }

    public function permissions($id)
    {
        Gate::authorize('manage-roles');
        return response()->json($this->rolePermissionService->getRolePermissions((int) $id));
    }

    public function syncPermissions(Request $request, $id)
    {
        Gate::authorize('manage-roles');

        $request->validate([
            'permissions' => 'present|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $this->rolePermissionService->syncRolePermissions(
            (int) $id,
            $request->permissions,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json(['message' => 'Permissions updated successfully.']);
    }
}
