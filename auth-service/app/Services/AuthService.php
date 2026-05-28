<?php

namespace App\Services;

use App\Models\User;
use App\Mail\PasswordResetMail;
use App\Mail\VerifyEmail;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\SessionRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Laravel\Sanctum\PersonalAccessToken;
use App\Services\InternalAuditService;

class AuthService
{
    protected UserRepositoryInterface $userRepo;
    protected SessionRepositoryInterface $sessionRepo;
    protected AuditLogRepositoryInterface $auditLogRepo;
    protected InternalAuditService $internalAuditService;

    public function __construct(
        UserRepositoryInterface $userRepo,
        SessionRepositoryInterface $sessionRepo,
        AuditLogRepositoryInterface $auditLogRepo,
        InternalAuditService $internalAuditService
    ) {
        $this->userRepo = $userRepo;
        $this->sessionRepo = $sessionRepo;
        $this->auditLogRepo = $auditLogRepo;
        $this->internalAuditService = $internalAuditService;
    }

    public function attemptLogin(string $email, string $password, string $ip, string $userAgent): array
    {
        $throttleKey = 'login:' . Str::lower($email) . '|' . $ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                    'errors' => ['email' => ['Account temporarily locked.']]
                ], 429)
            );
        }

        $user = $this->userRepo->findByEmail($email, ['profile.role', 'profile.department', 'credentials']);

        if (!$user || !Hash::check($password, $user->credentials->password_hash)) {
            RateLimiter::hit($throttleKey, 900); // 15 minutes lockout

            $this->auditLogRepo->log(
                $user ? $user->id : null,
                'LOGIN_FAILED',
                'Failed login attempt for email: ' . $email,
                $ip,
                $userAgent
            );

            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $refreshTokenPlain = Str::random(128);
        $refreshTokenHash = hash('sha256', $refreshTokenPlain);
        $sessionId = (string) Str::uuid();

        // Write security-critical records synchronously
        $this->sessionRepo->createRefreshToken($user->id, $refreshTokenHash, $ip, $userAgent);
        $this->sessionRepo->createSession($user->id, $sessionId, $ip, $userAgent);

        // Log success deferred
        defer(function () use ($user, $ip, $userAgent, $email) {
            $this->auditLogRepo->log(
                $user->id,
                'LOGIN_SUCCESS',
                'Successful login for email: ' . $email,
                $ip,
                $userAgent
            );

            if ($user->profile?->department?->name === 'Finance') {
                $this->internalAuditService->pushEvent(
                    'Login Success',
                    'Session',
                    $user->id,
                    [
                        'email' => $email,
                        'ip_address' => $ip,
                        'user_agent' => $userAgent,
                    ],
                    $user
                );
            }
        });

        $permissions = $user->profile?->role?->permissions()
            ?->where('system', 'crms')
            ?->pluck('slug') ?? collect();

        $user->unsetRelation('credentials');

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenPlain,
            'session_id' => $sessionId,
            'user' => $this->formatUserForFrontend($user),
            'user_model' => $user,
            'permissions' => $permissions
        ];
    }

    public function refreshSession(string $refreshTokenPlain, string $ip, string $userAgent): array
    {
        $tokenHash = hash('sha256', $refreshTokenPlain);
        $tokenRecord = $this->sessionRepo->findRefreshToken($tokenHash);

        if (!$tokenRecord || now()->greaterThan($tokenRecord->expires_at)) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Invalid or expired refresh token.']
            ]);
        }

        // Reuse Detection
        if ($tokenRecord->is_revoked) {
            $this->sessionRepo->revokeAllRefreshTokens($tokenRecord->user_id);
            throw ValidationException::withMessages([
                'refresh_token' => ['Token compromise detected. All sessions revoked.']
            ]);
        }

        // Rotation
        $this->sessionRepo->revokeRefreshToken($tokenHash);

        $newRefreshTokenPlain = Str::random(128);
        $newRefreshTokenHash = hash('sha256', $newRefreshTokenPlain);

        $this->sessionRepo->createRefreshToken($tokenRecord->user_id, $newRefreshTokenHash, $ip, $userAgent);

        $user = $this->userRepo->findById($tokenRecord->user_id);

        if (!$user || !$user->is_active) {
            throw ValidationException::withMessages([
                'refresh_token' => ['User is inactive or not found.']
            ]);
        }

        $accessToken = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshTokenPlain,
            'user' => $this->formatUserForFrontend($user),
            'user_model' => $user
        ];
    }

    private function formatUserForFrontend(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'is_active' => (bool) $user->is_active,
            'profile' => $user->profile ? [
                'first_name' => $user->profile->first_name,
                'last_name' => $user->profile->last_name,
                'phone' => $user->profile->phone,
                'address' => $user->profile->address,
                'role' => $user->profile->role ? [
                    'name' => $user->profile->role->name,
                ] : null,
                'department' => $user->profile->department ? [
                    'name' => $user->profile->department->name,
                ] : null,
            ] : null,
        ];
    }

    public function logout(?string $refreshTokenPlain, ?string $sessionId, ?User $user, ?string $ip = null, ?string $userAgent = null): void
    {
        if ($refreshTokenPlain) {
            $tokenHash = hash('sha256', $refreshTokenPlain);
            $this->sessionRepo->revokeRefreshToken($tokenHash);
        }

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        if ($sessionId) {
            $this->sessionRepo->invalidateSession($sessionId);
        }

        if ($user && $ip && $userAgent) {
            $this->auditLogRepo->log(
                $user->id,
                'Logout',
                'User logged out: ' . $user->email,
                $ip,
                $userAgent
            );

            $user->load(['profile.department', 'profile.role']);
            if ($user->profile?->department?->name === 'Finance') {
                $this->internalAuditService->pushEvent(
                    'Logout',
                    'Session',
                    $user->id,
                    [
                        'email' => $user->email,
                        'ip_address' => $ip,
                        'user_agent' => $userAgent,
                    ],
                    $user
                );
            }
        }
    }

    public function sendPasswordReset(string $email, string $ip): void
    {
        $rateLimitKey = "pwd_reset:{$ip}";
        $windowStart = now()->subHour();

        $hits = $this->sessionRepo->getRateLimitCount($rateLimitKey, $windowStart);

        if ($hits >= 3) {
            throw new HttpResponseException(
                response()->json(['message' => 'Too many password reset attempts. Please try again later.'], 429)
                    ->header('Retry-After', 3600)
            );
        }

        $this->sessionRepo->logRateLimit($rateLimitKey);

        $user = $this->userRepo->findByEmail($email);

        if ($user) {
            $tokenPlain = Str::random(64);
            $tokenHash = hash('sha256', $tokenPlain);

            $this->sessionRepo->createPasswordResetToken($user->id, $tokenHash);

            Mail::to($user->email)->queue(new PasswordResetMail($tokenPlain));
        }
    }

    public function resetPassword(string $tokenPlain, string $password): void
    {
        $tokenHash = hash('sha256', $tokenPlain);
        $tokenRecord = $this->sessionRepo->findPasswordResetToken($tokenHash);

        if (!$tokenRecord) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired password reset token.']
            ]);
        }

        $user = $this->userRepo->findById($tokenRecord->user_id);

        if (!$user) {
            throw ValidationException::withMessages([
                'token' => ['User not found.']
            ]);
        }

        DB::transaction(function () use ($user, $tokenRecord, $password) {
            $this->userRepo->updatePasswordHash(
                $user->id,
                Hash::make($password, ['rounds' => 12]),
                false
            );

            $this->sessionRepo->usePasswordResetToken($tokenRecord->id);
            $this->sessionRepo->invalidateAllSessions($user->id);
            $this->sessionRepo->revokeAllRefreshTokens($user->id);
        });
    }

    public function sendVerificationEmail(User $user): void
    {
        if ($user->email_verified) {
            throw ValidationException::withMessages([
                'email' => ['Email already verified.']
            ]);
        }

        $rateLimitKey = "email_verify:{$user->id}";
        $windowStart = now()->subHours(24);

        $hits = $this->sessionRepo->getRateLimitCount($rateLimitKey, $windowStart);

        if ($hits >= 3) {
            throw new HttpResponseException(
                response()->json(['message' => 'Too many verification attempts. Please try again later.'], 429)
            );
        }

        $this->sessionRepo->logRateLimit($rateLimitKey);

        $tokenPlain = Str::random(64);
        $tokenHash = hash('sha256', $tokenPlain);

        $this->sessionRepo->createEmailVerificationToken($user->id, $tokenHash);

        $url = config('app.frontend_url', 'http://localhost:5173') . '/verify-email?token=' . $tokenPlain;
        Mail::to($user->email)->queue(new VerifyEmail($url));
    }

    public function verifyEmail(string $tokenPlain): void
    {
        $tokenHash = hash('sha256', $tokenPlain);
        $tokenRecord = $this->sessionRepo->findEmailVerificationToken($tokenHash);

        if (!$tokenRecord) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired verification token.']
            ]);
        }

        $user = $this->userRepo->findById($tokenRecord->user_id);

        if (!$user) {
            throw ValidationException::withMessages([
                'token' => ['User not found.']
            ]);
        }

        if ($user->email_verified) {
            return;
        }

        DB::transaction(function () use ($user, $tokenRecord) {
            $this->userRepo->update($user->id, [
                'email_verified' => true,
                'email_verified_at' => now(),
            ]);

            $this->sessionRepo->useEmailVerificationToken($tokenRecord->id);
        });
    }

    public function verifyAccessToken(string $token): array
    {
        if (str_contains($token, '|')) {
            $token = explode('|', $token)[1];
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            throw new HttpResponseException(
                response()->json(['valid' => false, 'message' => 'Invalid or expired token.'], 401)
            );
        }

        $user = $accessToken->tokenable->load(['profile.role.permissions', 'profile.department']);

        return [
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->profile->first_name,
                'last_name' => $user->profile->last_name,
                'role' => $user->profile->role->name,
                'department' => $user->profile->department->name,
                'permissions' => $user->profile->role->permissions->pluck('slug')
            ]
        ];
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword, string $ip, string $userAgent): void
    {
        $user->load('credentials');
        if (!Hash::check($currentPassword, $user->credentials->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password is incorrect.']
            ]);
        }

        $this->userRepo->updatePasswordHash(
            $user->id,
            Hash::make($newPassword),
            false
        );

        $this->auditLogRepo->log(
            $user->id,
            'PASSWORD_CHANGED',
            'User changed password: ' . $user->email,
            $ip,
            $userAgent
        );

        $this->internalAuditService->pushEvent(
            'password_changed',
            'User',
            $user->id,
            [
                'email' => $user->email,
            ],
            $user
        );
    }
}
