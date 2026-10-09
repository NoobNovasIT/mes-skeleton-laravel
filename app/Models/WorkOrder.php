<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    // Trang học đầu tiên vẫn chạy trên SQLite, không đụng database MASTERDATA.
    protected $connection = 'sqlite';

    protected $fillable = ['wo_no', 'model', 'qty', 'status'];
}
