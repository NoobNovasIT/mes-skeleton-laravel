<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** = Mes.Data/Entities/Language.cs. Chỉ đọc. */
class Language extends Model
{
    protected $table = 'Language';

    protected $primaryKey = 'LanguageCode';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'SortOrder' => 'integer',
            'IsDefault' => 'boolean',
            'IsActive' => 'boolean',
        ];
    }
}
