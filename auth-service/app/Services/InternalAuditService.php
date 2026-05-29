<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class InternalAuditService
{
    public function pushEvent(string $action, string $entityType, ?int $userId, array $context, ?User $actor = null): void
    {
        $url = env('VENDOR_MANAGEMENT_URL', 'http://vendor-management:8000/api') . '/internal/audit-event';
        $secret = env('INTERNAL_SERVICE_SECRET');

        if (!$secret) {
            Log::warning('INTERNAL_SERVICE_SECRET is not configured. CRMS Audit Log push skipped.');
            return;
        }

        $userDetails = [];
        if ($actor) {
            $actor->load(['profile.role', 'profile.department']);
            $firstName = $actor->profile->first_name ?? '';
            $lastName = $actor->profile->last_name ?? '';
            $fullName = trim("{$firstName} {$lastName}");
            $userDetails = [
                'user_name' => !empty($fullName) ? $fullName : $actor->email,
                'user_email' => $actor->email,
                'user_role' => $actor->profile->role->name ?? null,
                'user_department' => $actor->profile->department->name ?? null,
            ];
        }

        try {
            Http::withHeaders([
                'X-Internal-Secret' => $secret,
                'Accept'            => 'application/json',
            ])->asForm()->timeout(2)->connectTimeout(1)->post($url, array_merge([
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => 0,
                'user_id' => $userId,
                'new_data' => $context,
            ], $userDetails));
        } catch (\Exception $e) {
            Log::error('Failed to push audit event to CRMS: ' . $e->getMessage());
        }
    }
}
