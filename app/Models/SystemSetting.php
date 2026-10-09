<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/** = Mes.Data/Entities/SystemSetting.cs. Khóa chính là chuỗi SettingKey. */
class SystemSetting extends Model
{
    use Auditable;

    protected $table = 'SystemSetting';

    protected $primaryKey = 'SettingKey';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['UpdatedAt' => 'datetime'];
    }

    protected function auditColumnNames(): array
    {
        return ['UpdatedAt', 'UpdatedByUserAccountId'];
    }
}
