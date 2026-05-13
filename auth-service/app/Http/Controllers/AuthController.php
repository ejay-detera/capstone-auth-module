<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $throttleKey = 'login:' . Str::lower($request->username) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                'errors' => ['username' => ['Account temporarily locked.']]
            ], 429);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->credentials->password_hash)) {
            RateLimiter::hit($throttleKey, 900); // 15 minutes lockout

            DB::table('audit_logs')->insert([
                'actor_id' => $user ? $user->id : null,
                'action' => 'LOGIN_FAILED',
                'description' => 'Failed login attempt for username: ' . $request->username,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'action_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'username' => ['Invalid username or password.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Success logic
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $refreshTokenPlain = Str::random(128);
        $refreshTokenHash = hash('sha256', $refreshTokenPlain);

        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => $refreshTokenHash,
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);

        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => Str::uuid(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        DB::table('audit_logs')->insert([
            'actor_id' => $user->id,
            'action' => 'LOGIN_SUCCESS',
            'description' => 'Successful login for username: ' . $request->username,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'action_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'user' => $user
        ])->cookie(
            'refresh_token', 
            $refreshTokenPlain, 
            60 * 24 * 30, // 30 days
            null, 
            null, 
            true, // Secure
            true, // HttpOnly
            false, 
            'Strict'
        );
    }
}
