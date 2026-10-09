<?php

namespace App\Services\AuditLogs;

use App\Services\Database\StoredProcedureExecutor;
use App\Support\Results\PagedResult;
use Carbon\CarbonImmutable;

/** = Mes.Service/AuditLogs/AuditLogService.cs. Truy vấn qua SP usp_System_AuditLog_Search (phân trang trong SQL). */
final class AuditLogService
{
    public function search(
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate,
        ?string $actionType,
        ?string $keyword,
        int $pageNumber,
        int $pageSize,
    ): PagedResult {
        $pageNumber = max(1, $pageNumber);

        $records = StoredProcedureExecutor::query(StoredProcedureExecutor::SystemAuditLogSearch, [
            'FromDate' => $fromDate->startOfDay()->format('Y-m-d H:i:s'),
            // ToDate là ngày cuối cùng được chọn -> lọc "< ngày hôm sau".
            'ToDate' => $toDate->startOfDay()->addDay()->format('Y-m-d H:i:s'),
            'ActionType' => $actionType === '' ? null : $actionType,
            'Keyword' => $keyword === '' ? null : $keyword,
            'PageNumber' => $pageNumber,
            'PageSize' => $pageSize,
        ]);

        $totalCount = $records === [] ? 0 : (int) $records[0]->TotalCount;
        $auditLogs = array_map(fn (\stdClass $record) => new AuditLogDto(
            (int) $record->Id,
            $record->UserName,
            $record->ActionType,
            $record->EntityName,
            $record->EntityId,
            $record->Description,
            $record->OldValueJson,
            $record->NewValueJson,
            $record->IpAddress,
            CarbonImmutable::parse($record->CreatedAt),
        ), $records);

        return new PagedResult($auditLogs, $totalCount, $pageNumber, $pageSize);
    }
}
