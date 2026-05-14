<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class CheckActiveSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionId = $request->cookie('session_id') ?? $request->header('X-Session-ID');

        if (!$sessionId) {
            return response()->json(['message' => 'Unauthenticated or session missing.'], 401);
        }

        $session = DB::table('user_sessions')
            ->where('session_id', $sessionId)
            ->first();

        if (!$session || !$session->is_active) {
            return response()->json(['message' => 'Session is inactive or invalid.'], 401);
        }

        // Check for 2-hour inactivity (120 minutes)
        $lastActive = \Carbon\Carbon::parse($session->last_active_at);
        if ($lastActive->diffInMinutes(now()) > 120) {
            DB::table('user_sessions')->where('session_id', $sessionId)->update(['is_active' => false]);
            return response()->json(['message' => 'Session expired due to inactivity.'], 401);
        }

        // Update last active timestamp
        DB::table('user_sessions')->where('session_id', $sessionId)->update(['last_active_at' => now()]);

        $user = $request->user();

        if (!$user || !$user->is_active) {
            return response()->json(['message' => 'User account is inactive.'], 401);
        }

        return $next($request);
    }
}
