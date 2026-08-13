<?php

namespace Modules\Admission\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Admission\Imports\ApplicationsImport;
use Modules\Admission\Models\AdmissionImportRun;
use Throwable;

class AdmissionImportService
{
    public function import(UploadedFile $file, ?int $adminId, bool $restoreStatus = false): AdmissionImportRun
    {
        $run = AdmissionImportRun::query()->create([
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'processing',
            'imported_by' => $adminId,
            'started_at' => now(),
        ]);

        $importer = new ApplicationsImport($run, $restoreStatus);

        try {
            Excel::import($importer, $file);

            $run->update([
                'status' => 'completed',
                'total_rows' => $importer->totalRows(),
                'success_rows' => $importer->successRows(),
                'failed_rows' => $importer->failedRows(),
                'created_rows' => $importer->createdRows(),
                'updated_rows' => $importer->updatedRows(),
                'completed_at' => now(),
                'fatal_error' => null,
            ]);
        } catch (Throwable $e) {
            $run->update([
                'status' => 'failed',
                'total_rows' => $importer->totalRows(),
                'success_rows' => $importer->successRows(),
                'failed_rows' => $importer->failedRows(),
                'created_rows' => $importer->createdRows(),
                'updated_rows' => $importer->updatedRows(),
                'completed_at' => now(),
                'fatal_error' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            Log::error('Admission import failed.', [
                'import_run_id' => $run->id,
                'exception' => $e::class,
            ]);

            throw $e;
        }

        return $run->fresh();
    }

    public function runs(int $perPage = 20): LengthAwarePaginator
    {
        return AdmissionImportRun::query()
            ->latest('id')
            ->paginate($perPage);
    }

    public function errorsForRun(AdmissionImportRun $run, int $perPage = 50): LengthAwarePaginator
    {
        return $run->errors()
            ->orderBy('row_number')
            ->paginate($perPage);
    }

    public function clearLogs(): int
    {
        return DB::transaction(function (): int {
            $count = AdmissionImportRun::query()->count();

            // admission_import_errors are removed by the FK cascade.
            AdmissionImportRun::query()->delete();

            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement('ALTER TABLE `admission_import_runs` AUTO_INCREMENT = 1');
                DB::statement('ALTER TABLE `admission_import_errors` AUTO_INCREMENT = 1');
            } elseif ($driver === 'sqlite') {
                DB::table('sqlite_sequence')
                    ->whereIn('name', ['admission_import_runs', 'admission_import_errors'])
                    ->delete();
            }

            return $count;
        });
    }
}
