<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSoftDeleteFlag;
use App\Support\Enums\MenuType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * = Mes.Data/Entities/Menu.cs. Cây ParentMenuId, MenuType 1 Module / 2 Group / 3 Page.
 *
 * @property int $Id
 * @property ?int $ParentMenuId
 * @property string $MenuCode
 * @property MenuType $MenuType
 * @property string $DefaultName
 * @property ?string $Url
 * @property ?string $IconClass
 * @property int $SortOrder
 * @property bool $IsActive
 * @property bool $IsVisibleInNavigation
 */
class Menu extends Model
{
    use Auditable;
    use HasSoftDeleteFlag;

    protected $table = 'Menu';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $guarded = ['Id'];

    protected function casts(): array
    {
        return [
            'ParentMenuId' => 'integer',
            'MenuType' => MenuType::class,
            'SortOrder' => 'integer',
            'IsActive' => 'boolean',
            'IsVisibleInNavigation' => 'boolean',
            'CreatedAt' => 'datetime',
            'UpdatedAt' => 'datetime',
            'IsDeleted' => 'boolean',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MenuTranslation::class, 'MenuId', 'Id');
    }

    /** Tên theo ngôn ngữ, thiếu bản dịch dùng DefaultName (Menu.GetDisplayName bên C#). */
    public function displayName(string $languageCode): string
    {
        $translation = $this->translations->firstWhere('LanguageCode', $languageCode);

        return $translation?->DisplayName ?? $this->DefaultName;
    }
}
