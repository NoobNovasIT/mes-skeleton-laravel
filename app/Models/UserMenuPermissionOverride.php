<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCompositeKey;
use App\Support\Enums\AccessLevel;
use Illuminate\Database\Eloquent\Model;

/** = Mes.Data/Entities/UserMenuPermissionOverride.cs. Có dòng = dùng mức này, bỏ qua quyền từ Role. */
class UserMenuPermissionOverride extends Model
{
    use Auditable;
    use HasCompositeKey;

    protected $table = 'UserMenuPermissionOverride';

    protected $primaryKey = 'UserAccountId';

    /** @var list<string> */
    protected array $compositeKeyNames = ['UserAccountId', 'MenuId'];

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
