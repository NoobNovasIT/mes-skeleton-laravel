<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSoftDeleteFlag;
use Illuminate\Database\Eloquent\Model;

/**
 * Phiếu nhận vật tư (Warehouse > Raw Warehouse > Receive Material).
 * Bảng tạo bằng database/MASTERDATA_SQL/013_Create_Table_MaterialReceipt.sql
 * (bản ASP.NET hiện vẫn để dữ liệu trong bộ nhớ).
 *
 * @property int $Id
 * @property string $ReceiptCode
 * @property string $MaterialCode
 * @property string $MaterialName
 * @property string $ReceivedQuantity
 * @property \Illuminate\Support\Carbon $ReceivedAt
 * @property ?string $Note
 */
class MaterialReceipt extends Model
{
    use Auditable;
    use HasSoftDeleteFlag;

    protected $table = 'MaterialReceipt';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $guarded = ['Id'];

    protected function casts(): array
    {
        return [
            'ReceivedQuantity' => 'decimal:3',
            'ReceivedAt' => 'datetime',
            'CreatedAt' => 'datetime',
            'UpdatedAt' => 'datetime',
            'IsDeleted' => 'boolean',
        ];
    }
}
