<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCompositeKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** = Mes.Data/Entities/UserAccountRole.cs. Bảng nối, khóa ghép (UserAccountId, RoleId), xóa cứng. */
class UserAccountRole extends Model
{
    use Auditable;
    use HasCompositeKey;

    protected $table = 'UserAccountRole';

    protected $primaryKey = 'UserAccountId';

    /** @var list<string> */
    protected array $compositeKeyNames = ['UserAccountId', 'RoleId'];

    public $timestamps = false;

    protected $guarded = [];

    protected function auditColumnNames(): array
    {
        return ['CreatedAt', 'CreatedByUserAccountId'];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'RoleId', 'Id');
    }

    public function userAccount(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'UserAccountId', 'Id');
    }
}
