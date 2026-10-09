<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;

/**
 * = Mes.Repository/StoredProcedures/StoredProcedureExecutor.cs (Dapper).
 * Gọi SP bằng EXEC với tham số đặt tên; kết quả là mảng stdClass, thuộc tính = tên cột trả về.
 *
 *   StoredProcedureExecutor::query('dbo.usp_Security_Menu_GetNavigationByUserAccountId',
 *       ['UserAccountId' => 1, 'LanguageCode' => 'vi']);
 */
final class StoredProcedureExecutor
{
    public const SecurityMenuGetNavigationByUserAccountId = 'dbo.usp_Security_Menu_GetNavigationByUserAccountId';
    public const SecurityPermissionGetMatrixByRoleId = 'dbo.usp_Security_Permission_GetMatrixByRoleId';
    public const SystemAuditLogSearch = 'dbo.usp_System_AuditLog_Search';

    /**
     * @param array<string, mixed> $parameters
     * @return list<\stdClass>
     */
    public static function query(string $storedProcedureName, array $parameters = []): array
    {
        $parameterSql = implode(', ', array_map(fn (string $parameterName) => '@'.$parameterName.' = ?', array_keys($parameters)));

        return DB::select(trim('EXEC '.$storedProcedureName.' '.$parameterSql), array_values($parameters));
    }
}
