<?php

namespace App\Services\AuditLogs;

use App\Support\CurrentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * = IAuditLogWriter + phần ghi AuditLog của interceptor.
 * Bản C# gom dòng AuditLog rồi lưu ở SaveChanges; ở đây INSERT ngay - Service bọc nghiệp vụ trong
 * DB::transaction() nên lỗi giữa chừng thì AuditLog cũng rollback theo.
 * Ghi bằng query builder (không qua Model) để chính bảng AuditLog không bị audit (INotAudited).
 */
final class AuditLogWriter
{
    /** Sự kiện không phải CRUD của người đang đăng nhập (PermissionChange, PasswordChanged...). */
    public static function add(string $actionType, ?string $description, ?string $entityName = null, ?string $entityId = null): void
    {
        self::insert(CurrentUser::userAccountId(), CurrentUser::userName(), $actionType, $description, $entityName, $entityId, null, null);
    }

    /** Khi chưa đăng nhập (Login, LoginFailed) - truyền người dùng vào thay vì đọc từ phiên. */
    public static function addForUser(?int $userAccountId, ?string $userName, string $actionType, ?string $description): void
    {
        self::insert($userAccountId, $userName, $actionType, $description, 'UserAccount', $userAccountId === null ? null : (string) $userAccountId, null, null);
    }

    /** Gọi từ trait Auditable (Create / Update / Delete). */
    public static function addEntityChange(string $actionType, Model $model, ?array $oldValues, ?array $newValues): void
    {
        self::insert(
            CurrentUser::userAccountId(),
            CurrentUser::userName(),
            $actionType,
            null,
            class_basename($model),
            method_exists($model, 'auditEntityId') ? $model->auditEntityId() : (string) $model->getKey(),
            $oldValues,
            $newValues);
    }

    private static function insert(
        ?int $userAccountId,
        ?string $userName,
        string $actionType,
        ?string $description,
        ?string $entityName,
        ?string $entityId,
        ?array $oldValues,
        ?array $newValues,
    ): void {
        DB::table('AuditLog')->insert([
            'UserAccountId' => $userAccountId,
            'UserName' => $userName,
            'ActionType' => $actionType,
            'EntityName' => $entityName,
            'EntityId' => $entityId,
            'Description' => $description === null ? null : mb_substr($description, 0, 500),
            'OldValueJson' => self::serializeOrNull($oldValues),
            'NewValueJson' => self::serializeOrNull($newValues),
            'IpAddress' => CurrentUser::ipAddress(),
            'CreatedAt' => now(),
        ]);
    }

    private static function serializeOrNull(?array $values): ?string
    {
        return empty($values) ? null : json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
