<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSoftDeleteFlag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * = Mes.Data/Entities/Role.cs. IsSystemRole = 1 (ADMIN): không xóa / đổi RoleCode được, luôn FullAccess.
 *
 * @property int $Id
 * @property string $RoleCode
 * @property string $RoleName
 * @property ?string $Description
 * @property bool $IsSystemRole
 */
class Role extends Model
{
    use Auditable;
    use HasSoftDeleteFlag;

    protected $table = 'Role';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $guarded = ['Id'];

    protected function casts(): array
    {
        return [
            'IsSystemRole' => 'boolean',
            'CreatedAt' => 'datetime',
            'UpdatedAt' => 'datetime',
            'IsDeleted' => 'boolean',
        ];
    }

    public function userAccountRoles(): HasMany
    {
        return $this->hasMany(UserAccountRole::class, 'RoleId', 'Id');
    }

    public function menuPermissions(): HasMany
    {
        return $this->hasMany(RoleMenuPermission::class, 'RoleId', 'Id');
    }
}
