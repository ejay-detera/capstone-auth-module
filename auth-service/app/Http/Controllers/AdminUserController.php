<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Services\RolePermissionService;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdminUserController extends Controller
{
    protected UserService $userService;
    protected RolePermissionService $rolePermissionService;
    protected DepartmentService $departmentService;

    public function __construct(
        UserService $userService,
        RolePermissionService $rolePermissionService,
        DepartmentService $departmentService
    ) {
        $this->userService = $userService;
        $this->rolePermissionService = $rolePermissionService;
        $this->departmentService = $departmentService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'role_id', 'department_id', 'is_active']);
        $actor = $request->user();
        $actor->loadMissing(['profile.role', 'profile.department']);
        
        return $this->userService->paginateUsers(
            $filters,
            (int) $request->input('per_page', 15),
            $actor
        );
    }

    public function show($id)
    {
        return $this->userService->getUserById((int) $id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => ['required', 'email', 'unique:users,email', 'regex:/@sbsi\.com$/i'],
            'role_id'         => 'required_without:role_name|nullable|exists:roles,id',
            'role_name'       => 'required_without:role_id|nullable|string|exists:roles,name',
            'department_id'   => 'required_without:department_name|nullable|exists:departments,id',
            'department_name' => 'required_without:department_id|nullable|string|exists:departments,name',
        ], [
            'email.unique' => 'An account with this email already exists.',
            'email.regex'  => 'Email must use the company domain @sbsi.com.',
        ]);

        $user = $this->userService->createUser(
            $validated,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user
        ], 201);
    }

    public function getRoles(Request $request)
    {
        $actor = $request->user();
        $actor->loadMissing(['profile.role', 'profile.department']);
        $roles = $this->rolePermissionService->getRolesForUserCreation($actor);
        return response()->json($roles);
    }

    public function getDepartments(Request $request)
    {
        $actor = $request->user();
        $actor->loadMissing(['profile.role', 'profile.department']);
        $departments = $this->departmentService->getDepartmentsForUserCreation($actor);
        return response()->json($departments);
    }

    public function getUserPermissions($id)
    {
        // For security, only allow the user to see their own permissions or admins to see anyone's
        if (Auth::id() != $id && Gate::denies('manage-users')) {
            abort(403, 'Unauthorized.');
        }

        $permissions = $this->rolePermissionService->getUserPermissions((int) $id);
        return response()->json($permissions);
    }

    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $this->userService->toggleUserStatus(
            (int) $id,
            $request->password,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'User status updated successfully.',
            'user' => $user
        ]);
    }
}
