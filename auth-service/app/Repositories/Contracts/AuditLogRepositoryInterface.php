<?php

namespace App\Repositories\Contracts;

interface AuditLogRepositoryInterface
{
    public function log(?int $actorId, string $action, string $description, string $ip, string $userAgent): bool;
}
