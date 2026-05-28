<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(int $id, array $relations = []): ?User;

    public function findByEmail(string $email, array $relations = []): ?User;

    public function create(array $data): User;

    public function update(int $id, array $data): bool;

    public function updateStatus(int $userId, bool $isActive): bool;

    public function updatePasswordHash(int $userId, string $passwordHash, bool $mustChangePassword = false): bool;

    public function createProfile(int $userId, array $data): UserProfile;

    public function updateProfile(int $userId, array $data): UserProfile;

    public function createCredentials(int $userId, string $passwordHash, bool $mustChangePassword = true): bool;

    public function getUserPermissions(int $userId): array;

    public function paginateUsers(array $filters, int $perPage): LengthAwarePaginator;
}
