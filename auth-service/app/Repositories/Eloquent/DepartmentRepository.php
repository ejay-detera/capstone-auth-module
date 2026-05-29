<?php

namespace App\Repositories\Eloquent;

use App\Models\Department;
use App\Models\User;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function all(): array
    {
        return Department::withCount('users')->get()->toArray();
    }

    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = Department::withCount('users');
        if ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }
        return $query->paginate($perPage);
    }


    public function findById(int $id, array $relations = []): ?Department
    {
        return Department::withCount('users')->with($relations)->find($id);
    }

    public function findByName(string $name): ?Department
    {
        return Department::where('name', $name)->first();
    }

    public function create(array $data): Department
    {
        return Department::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $department = Department::findOrFail($id);
        return $department->update($data);
    }

    public function delete(int $id): bool
    {
        $department = Department::findOrFail($id);
        return $department->delete();
    }

    public function getUsers(int $departmentId, int $perPage = 15): LengthAwarePaginator
    {
        return User::whereHas('profile', function ($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })->with(['profile.role'])->paginate($perPage);
    }

    public function hasAssignedUsers(int $departmentId): bool
    {
        $department = Department::withCount('users')->findOrFail($departmentId);
        return $department->users_count > 0;
    }
}
