<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected RolePermissionService $rolePermissionService;

    public function __construct(
        AuthService $authService,
        RolePermissionService $rolePermissionService
    ) {
        $this->authService = $authService;
        $this->rolePermissionService = $rolePermissionService;
    }

    public function login(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Login Attempt Data:', $request->all());

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'login:' . Str::lower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                'errors' => ['email' => ['Account temporarily locked.']]
            ], 429);
        }

        $user = User::with(['profile.role', 'profile.department'])->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->credentials->password_hash)) {
            RateLimiter::hit($throttleKey, 900); // 15 minutes lockout

            DB::table('audit_logs')->insert([
                'actor_id' => $user ? $user->id : null,
                'action' => 'Login Failed',
                'description' => 'Failed login attempt for email: ' . $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'action_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($user && $user->profile?->department?->name === 'Finance') {
                $this->pushToCrmsAuditLog('Login Failed', 'Session', $user->id, [
                    'email' => $request->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Success logic
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $refreshTokenPlain = Str::random(128);
        $refreshTokenHash = hash('sha256', $refreshTokenPlain);
        $sessionId = (string) Str::uuid();

        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $email = $request->email;

        // Write security-critical records synchronously before sending the response.
        // defer() is NOT safe here — if the PHP process restarts between the response
        // being sent and defer() executing, the client holds a refresh_token cookie
        // with no matching DB record, causing all future requests to fail as "invalid".
        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => $refreshTokenHash,
            'ip_address' => $ip,
            'device_info' => $userAgent,
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);

        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        // Audit log is non-critical — deferring is fine here.
        defer(function () use ($user, $ip, $userAgent, $sessionId, $email) {
            DB::table('audit_logs')->insert([
                'actor_id' => $user->id,
                'action' => 'Login Success',
                'description' => 'Successful login for email: ' . $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'action_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($user->profile?->department?->name === 'Finance') {
                $this->pushToCrmsAuditLog('Login Success', 'Session', $user->id, [
                    'email' => $email,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ]);
            }
        });

        // Load permissions for the CRMS system specifically for this response
        $permissions = $user->profile?->role?->permissions()
            ?->where('system', 'crms')
            ?->pluck('slug') ?? collect();

        return response()->json([
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
            'session_id' => $result['session_id'],
            'user' => $result['user'],
            'permissions' => $result['permissions']
        ])->cookie(
            'refresh_token',
            $result['refresh_token'],
            60 * 24 * 30, // 30 days
            null,
            null,
            true, // Secure
            true, // HttpOnly
            false,
            'Strict'
        )->cookie(
            'session_id',
            $result['session_id'],
            60 * 24 * 30, // 30 days
            null,
            null,
            true, // Secure
            true, // HttpOnly
            false,
            'Strict'
        );
    }

    public function refresh(Request $request)
    {
        $refreshTokenPlain = $request->cookie('refresh_token');

        if (!$refreshTokenPlain) {
            return response()->json(['message' => 'Refresh token missing.'], 401);
        }

        try {
            $result = $this->authService->refreshSession(
                $refreshTokenPlain,
                $request->ip(),
                $request->userAgent()
            );

            return response()->json([
                'access_token' => $result['access_token'],
                'token_type' => 'Bearer',
                'user' => $result['user']
            ])->cookie(
                'refresh_token',
                $result['refresh_token'],
                60 * 24 * 30,
                null,
                null,
                true,
                true,
                false,
                'Strict'
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function logout(Request $request)
    {
        $refreshTokenPlain = $request->cookie('refresh_token');

        if ($refreshTokenPlain) {
            $tokenHash = hash('sha256', $refreshTokenPlain);
            DB::table('refresh_tokens')
                ->where('token_hash', $tokenHash)
                ->update(['is_revoked' => true]);
        }

        $user = $request->user();
        if ($user) {
            $user->load(['profile.department']);
            $department = $user->profile?->department?->name;

            DB::table('audit_logs')->insert([
                'actor_id' => $user->id,
                'action' => 'Logout',
                'description' => 'User logged out: ' . $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'action_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($department === 'Finance') {
                $this->pushToCrmsAuditLog('Logout', 'Session', $user->id, [
                    'email' => $user->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            $user->currentAccessToken()->delete();
        }

        $sessionId = $request->cookie('session_id') ?? $request->header('X-Session-ID');

        $this->authService->logout($refreshTokenPlain, $sessionId, $request->user());

        return response()->json(['message' => 'Successfully logged out.'])
            ->withoutCookie('refresh_token')
            ->withoutCookie('session_id');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $this->authService->sendPasswordReset($request->email, $request->ip());

        // Always return 200 for anti-enumeration
        return response()->json(['message' => 'If an account with that email exists, a password reset link has been sent.']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $this->authService->resetPassword($request->token, $request->password);
            return response()->json(['message' => 'Password has been successfully reset.']);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function sendVerification(Request $request)
    {
        $this->authService->sendVerificationEmail($request->user());
        return response()->json(['message' => 'Verification email sent.']);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        try {
            $this->authService->verifyEmail($request->token);
            return response()->json(['message' => 'Email verified successfully.']);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get permissions for the authenticated user, optionally filtered by system.
     */
    public function permissions(Request $request)
    {
        $system = $request->query('system');
        $permissions = $this->rolePermissionService->getUserPermissionsBySystem($request->user()->id, $system);

        return response()->json([
            'permissions' => $permissions
        ]);
    }

    /**
     * Internal endpoint for other services to verify a token.
     */
    public function verifyToken(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        // Sanctum uses | to separate ID from token
        $token = $request->token;
        if (str_contains($token, '|')) {
            $token = explode('|', $token)[1];
        }

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (!$accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            return response()->json(['valid' => false, 'message' => 'Invalid or expired token.'], 401);
        }

        $user = $accessToken->tokenable->load(['profile.role.permissions', 'profile.department']);

        return response()->json([
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
        ]);
    }

    /**
     * Push audit event to CRMS vendor-management service.
     */
    private function pushToCrmsAuditLog(string $action, string $entityType, ?int $userId, array $context): void
    {
        $url = env('VENDOR_MANAGEMENT_URL', 'http://vendor-management:8000/api') . '/internal/audit-event';
        $secret = env('INTERNAL_SERVICE_SECRET');

        if (!$secret) {
            \Illuminate\Support\Facades\Log::warning('INTERNAL_SERVICE_SECRET is not configured. CRMS Audit Log push skipped.');
            return;
        }

        try {
            \Illuminate\Support\Facades\Http::withHeaders([
                'X-Internal-Secret' => $secret,
            ])->timeout(2)->connectTimeout(1)->post($url, [
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => 0,
                'user_id' => $userId,
                'new_data' => $context,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to push audit event to CRMS: ' . $e->getMessage());
        }
    }
}
