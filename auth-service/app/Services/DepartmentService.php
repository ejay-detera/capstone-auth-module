<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Exceptions\HttpResponseException;

class DepartmentService
{
    protected UserRepositoryInterface $userRepo;
    protected DepartmentRepositoryInterface $departmentRepo;
    protected AuditLogRepositoryInterface $auditLogRepo;

    public function __construct(
        UserRepositoryInterface $userRepo,
        DepartmentRepositoryInterface $departmentRepo,
        AuditLogRepositoryInterface $auditLogRepo
    ) {
        $this->userRepo = $userRepo;
        $this->departmentRepo = $departmentRepo;
        $this->auditLogRepo = $auditLogRepo;
    }

    public function getAllDepartments(): array
    {
        try {
            return Cache::remember('departments:all', 3600, function () {
                return $this->departmentRepo->all();
            });
        } catch (\Exception $e) {
            Log::warning('Cache unavailable for departments:all, querying DB directly', ['error' => $e->getMessage()]);
            return $this->departmentRepo->all();
        }
    }

    public function getPaginatedDepartments(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->departmentRepo->paginate($perPage, $search);
    }

    public function getDepartmentsForUserCreation(?User $actor): array
    {
        $departments = $this->getAllDepartments();

        if ($actor && !in_array($actor->profile?->role?->name, ['IT Admin', 'Super Admin'])) {
            if ($actor->profile?->department?->name === 'Finance' && $actor->profile?->role?->name === 'Admin') {
                $departments = array_filter($departments, function($d) {
                    return $d['name'] === 'Finance';
                });
                $departments = array_values($departments);
            } else {
                $departments = [];
            }
        }

        return $departments;
    }

    public function createDepartment(array $data, ?User $actor, string $ip, string $userAgent): Department
    {
        $department = $this->departmentRepo->create($data);

        try { Cache::forget('departments:all'); } catch (\Exception $e) {}

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'DEPARTMENT_CREATED',
            "Created department: {$department->name}",
            $ip,
            $userAgent
        );

        return $department;
    }

    public function getDepartment(int $id): Department
    {
        $department = $this->departmentRepo->findById($id);
        if (!$department) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Department::class, $id);
        }
        return $department;
    }

    public function updateDepartment(int $id, array $data, ?User $actor, string $ip, string $userAgent): Department
    {
        $department = $this->departmentRepo->findById($id);
        if (!$department) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Department::class, $id);
        }

        $oldName = $department->name;
        $oldDesc = $department->description;

        $this->departmentRepo->update($id, $data);

        try { Cache::forget('departments:all'); } catch (\Exception $e) {}

        $department->refresh();

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'DEPARTMENT_UPDATED',
            "Updated department '{$oldName}'. Changes: Name[{$oldName} -> {$department->name}], Description[{$oldDesc} -> {$department->description}]",
            $ip,
            $userAgent
        );

        return $department;
    }

    public function deleteDepartment(int $id, ?User $actor, string $ip, string $userAgent): void
    {
        if ($this->departmentRepo->hasAssignedUsers($id)) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Cannot delete department with assigned users.',
                    'user_count' => $this->departmentRepo->findById($id)->users_count
                ], 409)
            );
        }

        $department = $this->departmentRepo->findById($id);
        $name = $department->name;

        $this->departmentRepo->delete($id);

        try { Cache::forget('departments:all'); } catch (\Exception $e) {}

        $this->auditLogRepo->log(
            $actor ? $actor->id : null,
            'DEPARTMENT_DELETED',
            "Deleted department: {$name}",
            $ip,
            $userAgent
        );
    }

    public function getDepartmentUsers(int $departmentId): LengthAwarePaginator
    {
        $department = $this->departmentRepo->findById($departmentId);
        if (!$department) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Department::class, $departmentId);
        }
        return $this->departmentRepo->getUsers($departmentId);
    }

    public function assignDepartment(int $userId, int $departmentId, ?User $actor, string $ip, string $userAgent): User
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(User::class, $userId);
        }

        $department = $this->departmentRepo->findById($departmentId);
        if (!$department) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Department::class, $departmentId);
        }

        try {
            DB::beginTransaction();

            $profile = $user->profile;
            $oldDeptName = $profile->department ? $profile->department->name : 'None';

            $profile->update(['department_id' => $department->id]);

            $this->auditLogRepo->log(
                $actor ? $actor->id : null,
                'USER_DEPARTMENT_CHANGED',
                "Assigned department {$department->name} to user {$user->email} (was {$oldDeptName})",
                $ip,
                $userAgent
            );

            DB::commit();

            return $user->load('profile.department');

        } catch (\Exception $e) {
            DB::rollBack();
            throw new HttpResponseException(
                response()->json(['message' => 'Failed to assign department.', 'error' => $e->getMessage()], 500)
            );
        }
    }
}
