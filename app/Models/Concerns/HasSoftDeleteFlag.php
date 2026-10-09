<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * = ISoftDelete + Global Query Filter của MesDbContext.
 *
 * Laravel có sẵn trait SoftDeletes nhưng dùng cột thời gian "deleted_at"; schema MES dùng cột bit IsDeleted
 * (unique index lọc WHERE IsDeleted = 0), nên viết trait riêng:
 *   - Mọi truy vấn tự thêm WHERE IsDeleted = 0.
 *   - $model->delete() chuyển thành UPDATE IsDeleted = 1 (Auditable vẫn ghi AuditLog "Delete").
 */
trait HasSoftDeleteFlag
{
    /** Auditable đọc cờ này để ghi đúng kiểu xóa mềm. */
    public bool $isSoftDeleting = false;

    public static function bootHasSoftDeleteFlag(): void
    {
        static::addGlobalScope('notDeleted', function (Builder $builder) {
            $builder->where($builder->getModel()->qualifyColumn('IsDeleted'), false);
        });
    }

    protected function performDeleteOnModel(): void
    {
        $this->isSoftDeleting = true;
        $this->setAttribute('IsDeleted', true);
        if (method_exists($this, 'fillAuditColumns')) {
            $this->fillAuditColumns(isCreating: false);
        }

        $this->setKeysForSaveQuery($this->newModelQuery())->update($this->getDirty());
        $this->syncOriginal();
    }
}
