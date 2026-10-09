<?php

namespace App\Services\AuditLogs;

use Carbon\CarbonImmutable;

final class AuditLogDto
{
    public function __construct(
        public readonly int $auditLogId,
        public readonly ?string $userName,
        public readonly string $actionType,
        public readonly ?string $entityName,
        public readonly ?string $entityId,
        public readonly ?string $description,
        public readonly ?string $oldValueJson,
        public readonly ?string $newValueJson,
        public readonly ?string $ipAddress,
        public readonly CarbonImmutable $createdAt,
    ) {
    }
}
