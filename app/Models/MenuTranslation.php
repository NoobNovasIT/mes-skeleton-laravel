<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCompositeKey;
use Illuminate\Database\Eloquent\Model;

/** = Mes.Data/Entities/MenuTranslation.cs. Khóa ghép (MenuId, LanguageCode). */
class MenuTranslation extends Model
{
    use Auditable;
    use HasCompositeKey;

    protected $table = 'MenuTranslation';

    protected $primaryKey = 'MenuId';

    /** @var list<string> */
    protected array $compositeKeyNames = ['MenuId', 'LanguageCode'];

    public $timestamps = false;

    protected $guarded = [];

    protected $keyType = 'string';

    protected function auditColumnNames(): array
    {
        return [];
    }
}
