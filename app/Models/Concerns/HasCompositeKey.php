<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent chỉ hỗ trợ khóa chính 1 cột. Bảng nối của MES dùng khóa ghép (RoleId, MenuId)...
 * nên model khai báo $compositeKeyNames, trait này thêm đủ các cột vào WHERE khi UPDATE / DELETE.
 * (EF Core thì khai báo HasKey(x => new { x.RoleId, x.MenuId }) là xong.)
 */
trait HasCompositeKey
{
    public function getIncrementing(): bool
    {
        return false;
    }

    protected function setKeysForSaveQuery($query): Builder
    {
        foreach ($this->compositeKeyNames as $keyName) {
            $query->where($keyName, '=', $this->getOriginal($keyName) ?? $this->getAttribute($keyName));
        }

        return $query;
    }

    protected function setKeysForSelectQuery($query): Builder
    {
        return $this->setKeysForSaveQuery($query);
    }
}
