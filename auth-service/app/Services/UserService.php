<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Mail\WelcomeEmail;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\SessionRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserService
{
    protected UserRepositoryInterface $userRepo;
    protected SessionRepositoryInterface $sessionRepo;
    protected AuditLogRepositoryInterface $auditLogRepo;
    protected RoleRepositoryInterface $roleRepo;
    protected DepartmentRepositoryInterface $departmentRepo;

    public function __construct(
        UserRepositoryInterface $userRepo,
        SessionRepositoryInterface $sessionRepo,
        AuditLogRepositoryInterface $auditLogRepo,
        RoleRepositoryInterface $roleRepo,
        DepartmentRepositoryInterface $departmentRepo
    ) {
        $this->userRepo = $userRepo;
        $this->sessionRepo = $sessionRepo;
        $this->auditLogRepo = $auditLogRepo;
        $this->roleRepo = $roleRepo;
        $this->departmentRepo = $departmentRepo;
    }

    public function paginateUsers(array $filters, int $perPage, ?User $actor): LengthAwarePaginator
    {
        if ($actor && $actor->profile?->role?->name !== 'IT Admin' && $actor->profile?->role?->name !== 'Super Admin') {
            if ($actor->profile?->department?->name === 'Finance' && $actor->profile?->role?->name === 'Admin') {
                $filters['department_limit_id'] = $actor->profile->department_id;
            } else {
                $filters['force_empty'] = true;
            }
        }

        return $this->userRepo->paginateUsers($filters, $perPage);
    }

    public function getUserById(int $id): User
    {
        $user = $this->userRepo->findById($id, ['profile.role', 'profile.department']);
        if (!$user) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(User::class, $id);
        }
        return $user;
    }

    public function createUser(array $validated, ?User $actor, string $ip, string $userAgent): User
    {
        // Resolve role_name -> role_id
        if (empty($validated['role_id']) && !empty($validated['role_name'])) {
            $role = $this->roleRepo->findByName($validated['role_name']);
            $validated['role_id'] = $role?->id;
        }

        // Resolve department_name -> department_id
        if (empty($validated['department_id']) && !empty($validated['department_name'])) {
            $dept = $this->departmentRepo->findByName($validated['department_name']);
            $validated['department_id'] = $dept?->id;
        }

        if (empty($validated['role_id']) || empty($validated['department_id'])) {
            throw new HttpResponseException(
                response()->json(['message' => 'Invalid role or department provided.'], 422)
            );
        }

        $actorRole = $actor?->profile?->role?->name ?? '';
        $actorDept = $actor?->profile?->department?->name ?? '';

        $isITAdmin = in_array($actorRole, ['IT Admin', 'Super Admin']);

        if (!$isITAdmin) {
            if ($actorDept === 'Finance' && $actorRole === 'Admin') {
                $financeDept = $this->departmentRepo->findByName('Finance');
                if ($financeDept && $validated['department_id'] != $financeDept->id) {
                    throw new HttpResponseException(
                        response()->json(['message' => 'You can only create accounts for the Finance department.'], 403)
                    );
                }

                $allowedRoleNames = ['Manager', 'Employee'];
                $assignedRole = $this->roleRepo->findById($validated['role_id']);
                if (!$assignedRole || !in_array($assignedRole->name, $allowedRoleNames)) {
                    throw new HttpResponseException(
                        response()->json(['message' => 'You are only authorized to assign Manager or Employee roles.'], 403)
                    );
                }
            } else {
                throw new HttpResponseException(
                    response()->json(['message' => 'Unauthorized to create accounts.'], 403)
                );
            }
        }

        $tempPassword = $this->generateSecurePassword();

        try {
            DB::beginTransaction();

            $user = $this->userRepo->create([
                'email' => $validated['email'],
                'is_active' => true,
            ]);

            $this->userRepo->createProfile($user->id, [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'role_id' => $validated['role_id'],
                'department_id' => $validated['department_id'],
            ]);

            $this->userRepo->createCredentials($user->id, Hash::make($tempPassword), true);

            $this->auditLogRepo->log(
                $actor ? $actor->id : null,
                'ACCOUNT_CREATED',
                'Admin created account for ' . $user->email,
                $ip,
                $userAgent
            );

            DB::commit();

            // Targeted cache invalidation
            try {
                Cache::forget('roles:list');
                Cache::forget('roles:all');
                Cache::forget('departments:all');
            } catch (\Exception $e) {
                Log::warning('Failed to invalidate cache after user creation', ['error' => $e->getMessage()]);
            }

            Mail::to($user->email)->queue(new WelcomeEmail($user->email, $tempPassword));

            return $user->load(['profile.role', 'profile.department']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new HttpResponseException(
                response()->json(['message' => 'Failed to create user.', 'error' => $e->getMessage()], 500)
            );
        }
    }

    public function toggleUserStatus(int $userId, string $adminPassword, User $admin, string $ip, string $userAgent): User
    {
        if (!Hash::check($adminPassword, $admin->credentials->password_hash)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.']
            ]);
        }

        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(User::class, $userId);
        }

        if ($user->id === $admin->id) {
            throw new HttpResponseException(
                response()->json(['message' => 'You cannot deactivate your own account.'], 422)
            );
        }

        $newStatus = !$user->is_active;

        try {
            DB::beginTransaction();

            $this->userRepo->updateStatus($user->id, $newStatus);

            if (!$newStatus) {
                // Deactivating: revoke sessions and refresh tokens
                $this->sessionRepo->invalidateAllSessions($user->id);
                $this->sessionRepo->revokeAllRefreshTokens($user->id);
            }

            $this->auditLogRepo->log(
                $admin->id,
                $newStatus ? 'USER_ACTIVATED' : 'USER_DEACTIVATED',
                ($newStatus ? 'Admin activated account for ' : 'Admin deactivated account for ') . $user->email,
                $ip,
                $userAgent
            );

            DB::commit();

            return $user->load(['profile.role', 'profile.department']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new HttpResponseException(
                response()->json(['message' => 'Failed to update user status.', 'error' => $e->getMessage()], 500)
            );
        }
    }

    private function generateSecurePassword(): string
    {
        return Str::random(8) . 'A1!'; // Meets policy: min 8 chars, 1 uppercase, 1 number, 1 special char
    }
}
