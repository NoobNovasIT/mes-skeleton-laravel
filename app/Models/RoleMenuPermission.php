<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCompositeKey;
use App\Support\Enums\AccessLevel;
use Illuminate\Database\Eloquent\Model;

/** = Mes.Data/Entities/RoleMenuPermission.cs. Không có dòng = NoAccess. */
class RoleMenuPermission extends Model
{
    use Auditable;
    use HasCompositeKey;

    protected $table = 'RoleMenuPermission';

    protected $primaryKey = 'RoleId';

    /** @var list<string> */
    protected array $compositeKeyNames = ['RoleId', 'MenuId'];

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'AccessLevel' => AccessLevel::class,
            'UpdatedAt' => 'datetime',
        ];
    }

    protected function auditColumnNames(): array
    {
        return ['UpdatedAt', 'UpdatedByUserAccountId'];
    }
}
