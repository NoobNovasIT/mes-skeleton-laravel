<?php

namespace App\Models\Concerns;

use App\Services\AuditLogs\AuditLogWriter;
use App\Support\Constants\AuditActionTypes;
use App\Support\CurrentUser;
use Illuminate\Database\Eloquent\Model;

/**
 * = Mes.Data/Interceptors/AuditSaveChangesInterceptor.cs, viết bằng Model Events của Eloquent.
 *
 *   creating / updating : tự điền CreatedAt, CreatedByUserAccountId, UpdatedAt, UpdatedByUserAccountId
 *   created             : AuditLog "Create" với toàn bộ cột (đọc SAU khi lưu để có Id identity)
 *   updated             : AuditLog "Update" chỉ các cột thay đổi (cũ / mới), bỏ qua cột vận hành
 *   deleted             : AuditLog "Delete" (xóa mềm: IsDeleted false -> true; xóa cứng: toàn bộ cột cũ)
 *
 * Model nào "use Auditable" là có ngay, không phải viết gì thêm - giống entity bên EF tự được interceptor xử lý.
 */
trait Auditable
{
    private const MaskedAttributeNames = ['PasswordHash', 'TokenHash'];

    private const IgnoredAttributeNames = [
        'LastLoginAt', 'FailedLoginCount', 'LockoutEndAt', 'SecurityStamp',
        'CreatedAt', 'CreatedByUserAccountId', 'UpdatedAt', 'UpdatedByUserAccountId',
    ];

    public static function bootAuditable(): void
    {
        static::creating(fn (Model $model) => $model->fillAuditColumns(isCreating: true));
        static::updating(fn (Model $model) => $model->fillAuditColumns(isCreating: false));

        static::created(function (Model $model) {
            AuditLogWriter::addEntityChange(
                AuditActionTypes::Create, $model, null, $model->auditableAttributes($model->getAttributes()));
        });

        static::updated(function (Model $model) {
            $oldValues = [];
            $newValues = [];
            foreach ($model->getChanges() as $attributeName => $newValue) {
                if (in_array($attributeName, self::IgnoredAttributeNames, true)) {
                    continue;
                }
                $oldValues[$attributeName] = $model->maskIfSensitive($attributeName, $model->getRawOriginal($attributeName));
                $newValues[$attributeName] = $model->maskIfSensitive($attributeName, $newValue);
            }

            // Chỉ đổi cột vận hành (LastLoginAt, FailedLoginCount...) -> không ghi, giống bản C#.
            if ($newValues !== []) {
                AuditLogWriter::addEntityChange(AuditActionTypes::Update, $model, $oldValues, $newValues);
            }
        });

        static::deleted(function (Model $model) {
            $isSoftDelete = property_exists($model, 'isSoftDeleting') && $model->isSoftDeleting;
            AuditLogWriter::addEntityChange(
                AuditActionTypes::Delete,
                $model,
                $isSoftDelete ? ['IsDeleted' => false] : $model->auditableAttributes($model->getRawOriginal()),
                $isSoftDelete ? ['IsDeleted' => true] : null);
        });
    }

    /** Cột audit bảng thực sự có. Bảng thiếu cột (RoleMenuPermission, SystemSetting...) ghi đè hàm này. */
    protected function auditColumnNames(): array
    {
        return ['CreatedAt', 'CreatedByUserAccountId', 'UpdatedAt', 'UpdatedByUserAccountId'];
    }

    public function fillAuditColumns(bool $isCreating): void
    {
        $auditColumnNames = $this->auditColumnNames();
        $now = now();
        $currentUserAccountId = CurrentUser::userAccountId();

        if ($isCreating && in_array('CreatedAt', $auditColumnNames, true)) {
            $this->setAttribute('CreatedAt', $now);
            $this->setAttribute('CreatedByUserAccountId', $currentUserAccountId);

            return;
        }

        // Bảng không có CreatedAt dùng UpdatedAt NOT NULL cho cả lần thêm mới.
        if (in_array('UpdatedAt', $auditColumnNames, true)) {
            $this->setAttribute('UpdatedAt', $now);
            $this->setAttribute('UpdatedByUserAccountId', $currentUserAccountId);
        }
    }

    /** Id dùng cho AuditLog.EntityId; khóa ghép nối bằng "|" như bản C#. */
    public function auditEntityId(): ?string
    {
        if (property_exists($this, 'compositeKeyNames')) {
            return implode('|', array_map(fn (string $keyName) => (string) $this->getAttribute($keyName), $this->compositeKeyNames));
        }

        $key = $this->getKey();

        return $key === null ? null : (string) $key;
    }

    /** @param array<string, mixed> $attributes */
    public function auditableAttributes(array $attributes): array
    {
        $auditableAttributes = [];
        foreach ($attributes as $attributeName => $attributeValue) {
            if (! in_array($attributeName, self::IgnoredAttributeNames, true)) {
                $auditableAttributes[$attributeName] = $this->maskIfSensitive($attributeName, $attributeValue);
            }
        }

        return $auditableAttributes;
    }

    public function maskIfSensitive(string $attributeName, mixed $attributeValue): mixed
    {
        return in_array($attributeName, self::MaskedAttributeNames, true) && $attributeValue !== null ? '***' : $attributeValue;
    }
}
