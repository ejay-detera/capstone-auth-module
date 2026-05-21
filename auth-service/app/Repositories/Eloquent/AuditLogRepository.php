<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function log(?int $actorId, string $action, string $description, string $ip, string $userAgent): bool
    {
        return DB::table('audit_logs')->insert([
            'actor_id' => $actorId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'action_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
