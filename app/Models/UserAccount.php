<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSoftDeleteFlag;
use App\Support\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * = Mes.Data/Entities/UserAccount.cs + UserAccountConfiguration.cs.
 * Kế thừa Authenticatable để dùng được Auth::login() của Laravel (thay cho Cookie Authentication).
 * Không dùng bảng "users" mặc định của Laravel: schema do script SQL quản lý (002_Create_Table_UserAccount.sql).
 *
 * @property int $Id
 * @property string $UserName
 * @property string $Email
 * @property string $FullName
 * @property string $PasswordHash
 * @property AccountStatus $AccountStatus
 */
class UserAccount extends Authenticatable
{
    use Auditable;
    use HasSoftDeleteFlag;

    protected $table = 'UserAccount';

    protected $primaryKey = 'Id';

    /** Cột audit do trait Auditable điền (giống interceptor), không dùng created_at / updated_at của Laravel. */
    public $timestamps = false;

    protected $guarded = ['Id'];

    protected $hidden = ['PasswordHash'];

    protected function casts(): array
    {
        return [
            'IsEmailVerified' => 'boolean',
            'AccountStatus' => AccountStatus::class,
            'FailedLoginCount' => 'integer',
            'LockoutEndAt' => 'datetime',
            'MustChangePassword' => 'boolean',
            'LastLoginAt' => 'datetime',
            'CreatedAt' => 'datetime',
            'UpdatedAt' => 'datetime',
            'IsDeleted' => 'boolean',
        ];
    }

    /** Laravel đọc mật khẩu qua hàm này (cột mặc định là "password"). */
    public function getAuthPassword(): string
    {
        return $this->PasswordHash;
    }

    /** Bảng không có cột remember_token -> tắt tính năng "remember me" token của Laravel. */
    public function getRememberTokenName(): string
    {
        return '';
    }

    /** Role chưa xóa (global scope của Role tự lọc IsDeleted = 0). */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'UserAccountRole', 'UserAccountId', 'RoleId');
    }

    public function userAccountRoles(): HasMany
    {
        return $this->hasMany(UserAccountRole::class, 'UserAccountId', 'Id');
    }

    /** @return list<string> */
    public function roleNames(): array
    {
        return $this->roles->sortBy('RoleName')->pluck('RoleName')->values()->all();
    }

    public function isTemporarilyLocked(): bool
    {
        return $this->LockoutEndAt !== null && $this->LockoutEndAt->isFuture();
    }
}
