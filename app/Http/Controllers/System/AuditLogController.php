<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\MesController;
use App\Services\AuditLogs\AuditLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** = Areas/System/Controllers/AuditLogController.cs (System > Audit Log). Mặc định 7 ngày gần nhất. */
final class AuditLogController extends MesController
{
    private const PageSize = 50;
    private const DefaultSearchDayRange = 7;

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function index(Request $request): mixed
    {
        $toDate = $this->parseDate($request->query('toDate')) ?? CarbonImmutable::today();
        $fromDate = $this->parseDate($request->query('fromDate')) ?? $toDate->subDays(self::DefaultSearchDayRange);
        $actionType = $request->query('actionType');
        $keyword = $request->query('keyword');

        return view('system.audit-log.index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'actionType' => $actionType,
            'keyword' => $keyword,
            'searchResult' => $this->auditLogService->search($fromDate, $toDate, $actionType, $keyword, $request->integer('pageNumber', 1), self::PageSize),
        ]);
    }

    private function parseDate(mixed $dateText): ?CarbonImmutable
    {
        if (! is_string($dateText) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateText) !== 1) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $dateText)->startOfDay();
    }
}
