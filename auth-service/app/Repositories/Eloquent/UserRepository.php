<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\UserProfile;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id, array $relations = []): ?User
    {
        return User::with($relations)->find($id);
    }

    public function findByEmail(string $email, array $relations = []): ?User
    {
        return User::with($relations)->where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = User::findOrFail($id);
        return $user->update($data);
    }

    public function updateStatus(int $userId, bool $isActive): bool
    {
        return User::where('id', $userId)->update(['is_active' => $isActive]) > 0;
    }

    public function updatePasswordHash(int $userId, string $passwordHash, bool $mustChangePassword = false): bool
    {
        return DB::table('user_credentials')
            ->where('user_id', $userId)
            ->update([
                'password_hash' => $passwordHash,
                'must_change_password' => $mustChangePassword,
                'password_changed_at' => now(),
                'updated_at' => now(),
            ]) >= 0;
    }

    public function createProfile(int $userId, array $data): UserProfile
    {
        return UserProfile::create(array_merge($data, ['user_id' => $userId]));
    }

    public function updateProfile(int $userId, array $data): UserProfile
    {
        return UserProfile::updateOrCreate(['user_id' => $userId], $data);
    }

    public function createCredentials(int $userId, string $passwordHash, bool $mustChangePassword = true): bool
    {
        return DB::table('user_credentials')->insert([
            'user_id' => $userId,
            'password_hash' => $passwordHash,
            'must_change_password' => $mustChangePassword,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getUserPermissions(int $userId): array
    {
        $user = User::with('profile.role.permissions')->find($userId);
        if (!$user || !$user->profile || !$user->profile->role) {
            return [];
        }
        return $user->profile->role->permissions->pluck('slug')->toArray();
    }

    public function paginateUsers(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = User::with(['profile.role', 'profile.department']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['role_id'])) {
            $query->filterByRole($filters['role_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->filterByDepartment($filters['department_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->filterByStatus($filters['is_active']);
        }

        if (isset($filters['department_limit_id'])) {
            $query->whereHas('profile', function($q) use ($filters) {
                $q->where('department_id', $filters['department_limit_id']);
            });
        } elseif (isset($filters['force_empty']) && $filters['force_empty'] === true) {
            $query->where('id', -1);
        }

        return $query->paginate($perPage);
    }
}
