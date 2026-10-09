<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * = MASTERDATA_SQL/RunAllScripts.ps1 / sqlcmd. Schema MES do script SQL quản lý (không dùng Laravel migrations).
 * Chạy một file, hoặc cả thư mục theo thứ tự tên file, trên kết nối mặc định (sqlsrv). Tách lô theo dòng "GO".
 *
 *   php artisan mes:run-sql database/MASTERDATA_SQL                                   (cả thư mục)
 *   php artisan mes:run-sql database/MASTERDATA_SQL/013_Create_Table_MaterialReceipt.sql
 */
final class RunSqlScript extends Command
{
    protected $signature = 'mes:run-sql {path : File .sql hoặc thư mục chứa các file .sql}';

    protected $description = 'Chạy SQL script (tách lô theo GO) trên database MASTERDATA';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));
        $scriptPaths = is_dir($path) ? glob(rtrim($path, '/\\').DIRECTORY_SEPARATOR.'*.sql') : [$path];
        sort($scriptPaths, SORT_STRING);

        if ($scriptPaths === [] || ! is_file($scriptPaths[0])) {
            $this->error("Không tìm thấy file .sql: {$path}");

            return self::FAILURE;
        }

        foreach ($scriptPaths as $scriptPath) {
            if (! $this->runScript($scriptPath)) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function runScript(string $scriptPath): bool
    {
        $scriptText = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($scriptPath));
        $batches = array_values(array_filter(
            array_map('trim', preg_split('/^\s*GO\s*;?\s*$/mi', $scriptText)),
            fn (string $batch) => $batch !== ''));

        foreach ($batches as $batchIndex => $batch) {
            try {
                DB::unprepared($batch);
            } catch (Throwable $exception) {
                $this->error(sprintf('%s - lô %d/%d lỗi: %s', basename($scriptPath), $batchIndex + 1, count($batches), $exception->getMessage()));

                return false;
            }
        }

        $this->info(sprintf('OK  %s (%d lô)', basename($scriptPath), count($batches)));

        return true;
    }
}
