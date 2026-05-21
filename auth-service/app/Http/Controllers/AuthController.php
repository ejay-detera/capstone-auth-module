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

        $result = $this->authService->attemptLogin(
            $request->email,
            $request->password,
            $request->ip(),
            $request->userAgent()
        );

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
        $result = $this->authService->verifyAccessToken($request->token);
        return response()->json($result);
    }
}
