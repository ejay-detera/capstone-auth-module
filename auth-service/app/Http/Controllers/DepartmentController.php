<?php

namespace App\Http\Controllers;

use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    protected DepartmentService $departmentService;

    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    public function index(Request $request)
    {
        Gate::authorize('manage-departments');
        return response()->json($this->departmentService->getPaginatedDepartments(
            (int) $request->input('per_page', 15),
            $request->input('search')
        ));
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-departments');

        $validated = $request->validate([
            'name' => 'required|string|unique:departments,name|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $department = $this->departmentService->createDepartment(
            $validated,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($department, 201);
    }

    public function show($id)
    {
        Gate::authorize('manage-departments');
        return response()->json($this->departmentService->getDepartment((int) $id));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('manage-departments');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $department = $this->departmentService->updateDepartment(
            (int) $id,
            $validated,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($department);
    }

    public function destroy(Request $request, $id)
    {
        Gate::authorize('manage-departments');

        $this->departmentService->deleteDepartment(
            (int) $id,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json(['message' => 'Department deleted successfully.']);
    }

    public function users($id)
    {
        Gate::authorize('manage-departments');
        return response()->json($this->departmentService->getDepartmentUsers((int) $id));
    }

    public function assignDepartment(Request $request, $userId)
    {
        Gate::authorize('manage-departments');

        $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $user = $this->departmentService->assignDepartment(
            (int) $userId,
            (int) $request->department_id,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Department assigned successfully.',
            'user' => $user
        ]);
    }
}
