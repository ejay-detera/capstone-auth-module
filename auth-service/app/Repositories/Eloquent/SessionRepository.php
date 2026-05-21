<?php

namespace App\Repositories\Eloquent;

use App\Models\EmailVerificationToken;
use App\Repositories\Contracts\SessionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SessionRepository implements SessionRepositoryInterface
{
    public function createSession(int $userId, string $sessionId, string $ip, string $userAgent): bool
    {
        return DB::table('user_sessions')->insert([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);
    }

    public function invalidateSession(string $sessionId): bool
    {
        return DB::table('user_sessions')
            ->where('session_id', $sessionId)
            ->update([
                'is_active' => false,
            ]) >= 0;
    }

    public function invalidateAllSessions(int $userId): bool
    {
        return DB::table('user_sessions')
            ->where('user_id', $userId)
            ->update([
                'is_active' => false,
            ]) >= 0;
    }

    public function createRefreshToken(int $userId, string $tokenHash, string $ip, string $deviceInfo): bool
    {
        return DB::table('refresh_tokens')->insert([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'ip_address' => $ip,
            'device_info' => $deviceInfo,
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);
    }

    public function findRefreshToken(string $tokenHash): ?object
    {
        return DB::table('refresh_tokens')
            ->where('token_hash', $tokenHash)
            ->first();
    }

    public function revokeRefreshToken(string $tokenHash): bool
    {
        return DB::table('refresh_tokens')
            ->where('token_hash', $tokenHash)
            ->update([
                'is_revoked' => true,
            ]) >= 0;
    }

    public function revokeAllRefreshTokens(int $userId): bool
    {
        return DB::table('refresh_tokens')
            ->where('user_id', $userId)
            ->update([
                'is_revoked' => true,
            ]) >= 0;
    }

    public function logRateLimit(string $key): bool
    {
        return DB::table('rate_limit_log')->insert([
            'key' => $key,
            'hits' => 1,
            'window_start' => now()
        ]);
    }

    public function getRateLimitCount(string $key, Carbon $windowStart): int
    {
        return DB::table('rate_limit_log')
            ->where('key', $key)
            ->where('window_start', '>=', $windowStart)
            ->count();
    }

    public function createPasswordResetToken(int $userId, string $tokenHash): bool
    {
        return DB::table('password_reset_tokens')->insert([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addMinutes(15),
            'created_at' => now()
        ]);
    }

    public function findPasswordResetToken(string $tokenHash): ?object
    {
        return DB::table('password_reset_tokens')
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function usePasswordResetToken(int $tokenId): bool
    {
        return DB::table('password_reset_tokens')
            ->where('id', $tokenId)
            ->update([
                'used_at' => now(),
                'updated_at' => now()
            ]) >= 0;
    }

    public function createEmailVerificationToken(int $userId, string $tokenHash): EmailVerificationToken
    {
        return EmailVerificationToken::create([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function findEmailVerificationToken(string $tokenHash): ?EmailVerificationToken
    {
        return EmailVerificationToken::where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function useEmailVerificationToken(int $tokenId): bool
    {
        $token = EmailVerificationToken::find($tokenId);
        if ($token) {
            return $token->update(['used_at' => now()]);
        }
        return false;
    }
}
