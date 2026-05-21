<?php

namespace App\Http\Controllers;

use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    protected RolePermissionService $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    public function index()
    {
        Gate::authorize('manage-roles');
        return response()->json($this->rolePermissionService->getAllPermissions());
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-roles');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug',
            'description' => 'nullable|string|max:500',
        ]);

        $permission = $this->rolePermissionService->createPermission(
            $validated,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($permission, 201);
    }

    public function show($id)
    {
        Gate::authorize('manage-roles');
        return response()->json($this->rolePermissionService->getPermission((int) $id));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('manage-roles');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $permission = $this->rolePermissionService->updatePermission(
            (int) $id,
            $validated,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($permission);
    }

    public function destroy(Request $request, $id)
    {
        Gate::authorize('manage-roles');

        $this->rolePermissionService->deletePermission(
            (int) $id,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json(['message' => 'Permission deleted successfully.']);
    }

    public function getRoles($id)
    {
        Gate::authorize('manage-roles');
        return response()->json($this->rolePermissionService->getPermissionRoles((int) $id));
    }

    public function syncRoles(Request $request, $id)
    {
        Gate::authorize('manage-roles');

        $request->validate([
            'role_ids' => 'present|array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        $this->rolePermissionService->syncPermissionRoles(
            (int) $id,
            $request->role_ids,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json(['message' => 'Roles updated successfully.']);
    }
}
