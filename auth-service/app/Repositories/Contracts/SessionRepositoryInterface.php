<?php

namespace App\Repositories\Contracts;

use App\Models\EmailVerificationToken;
use Carbon\Carbon;

interface SessionRepositoryInterface
{
    public function createSession(int $userId, string $sessionId, string $ip, string $userAgent): bool;

    public function invalidateSession(string $sessionId): bool;

    public function invalidateAllSessions(int $userId): bool;

    public function createRefreshToken(int $userId, string $tokenHash, string $ip, string $deviceInfo): bool;

    public function findRefreshToken(string $tokenHash): ?object;

    public function revokeRefreshToken(string $tokenHash): bool;

    public function revokeAllRefreshTokens(int $userId): bool;

    public function logRateLimit(string $key): bool;

    public function getRateLimitCount(string $key, Carbon $windowStart): int;

    public function createPasswordResetToken(int $userId, string $tokenHash): bool;

    public function findPasswordResetToken(string $tokenHash): ?object;

    public function usePasswordResetToken(int $tokenId): bool;

    public function createEmailVerificationToken(int $userId, string $tokenHash): EmailVerificationToken;

    public function findEmailVerificationToken(string $tokenHash): ?EmailVerificationToken;

    public function useEmailVerificationToken(int $tokenId): bool;
}
