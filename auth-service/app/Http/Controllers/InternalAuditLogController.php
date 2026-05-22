<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InternalAuditLogController extends Controller
{
    /**
     * Get auth audit logs for Finance department users.
     */
    public function index(Request $request)
    {
        $secret = $request->header('X-Internal-Secret');
        $expectedSecret = env('INTERNAL_SERVICE_SECRET');

        if (!$secret || $secret !== $expectedSecret) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $query = DB::table('audit_logs')
            ->join('users', 'audit_logs.actor_id', '=', 'users.id')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('departments', 'user_profiles.department_id', '=', 'departments.id')
            ->where('departments.name', 'Finance')
            ->select([
                'audit_logs.id',
                'audit_logs.actor_id as user_id',
                'users.email as user_email',
                'user_profiles.first_name',
                'user_profiles.last_name',
                'audit_logs.action',
                'audit_logs.description',
                'audit_logs.ip_address',
                'audit_logs.action_date as performed_at'
            ]);

        // Support optional action filter
        if ($request->has('action') && !empty($request->action)) {
            $query->where('audit_logs.action', $request->action);
        }

        // Support optional date filter
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('audit_logs.action_date', $request->date);
        }

        $logs = $query->orderBy('audit_logs.action_date', 'desc')
            ->limit(200) // retrieve a healthy batch for merging
            ->get();

        return response()->json([
            'logs' => $logs
        ]);
    }
}
