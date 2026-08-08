<?php

namespace Modules\Admission\Imports;

use Modules\Admission\Models\AdmissionApplication;
use App\Services\Data\DataTransformer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

use Maatwebsite\Excel\Concerns\{
    ToCollection,
    WithHeadingRow,
    SkipsEmptyRows
};

class ApplicationsImport implements
    ToCollection,
    WithHeadingRow,
    SkipsEmptyRows
{
    /**
     * ❌ KHÔNG IMPORT
     */
    protected array $exceptFields = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'noi_sinh_chi_tiet',
    ];

    protected DataTransformer $transformer;

    public function __construct()
    {
        $this->transformer = app(DataTransformer::class);
    }

    /**
     * 🚀 MAIN
     */
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            $model = new AdmissionApplication();

            foreach ($rows as $index => $row) {

                $rowIndex = $index + 2; // vì có heading row

                try {

                    $row = $row->toArray();

                    /**
                     * 🔤 Normalize column name
                     */
                    $normalizedRow = [];
                    foreach ($row as $key => $value) {
                        $column = $this->normalizeColumn($key);
                        $normalizedRow[$column] = $value;
                    }

                    /**
                     * ❌ Remove except fields
                     */
                    foreach ($this->exceptFields as $field) {
                        unset($normalizedRow[$field]);
                    }

                    /**
                     * 🔄 TRANSFORM DATA (CORE)
                     */
                    try {
                        $data = $this->transformer->transformInput($model, $normalizedRow);
                    } catch (\Throwable $e) {

                        $this->logFieldError($rowIndex, null, null, $e);
                        continue;
                    }

                    if (empty($data)) {
                        continue;
                    }

                    /**
                     * 🔑 KEY BẮT BUỘC
                     */
                    $key = $data['ma_dinh_danh']
                        ?? $data['mhs']
                        ?? null;

                    if (!$key) {
                        $this->logRowError($rowIndex, 'missing_key', $normalizedRow);
                        continue;
                    }

                    /**
                     * 🔍 UPSERT
                     */
                    $record = AdmissionApplication::where('ma_dinh_danh', $key)
                        ->orWhere('mhs', $key)
                        ->first();

                    if ($record) {
                        $record->update($data);
                    } else {
                        AdmissionApplication::create($data);
                    }

                } catch (\Throwable $e) {

                    $this->logRowError($rowIndex, $e->getMessage(), $row ?? []);
                    continue;
                }
            }
        });
    }

    /**
     * =========================
     * 🧠 HELPERS
     * =========================
     */

    protected function normalizeColumn($key)
    {
        return Str::of($key)
            ->lower()
            ->replace(' ', '_')
            ->toString();
    }

    /**
     * =========================
     * 🚨 LOGGING
     * =========================
     */

    protected function logFieldError($rowIndex, $field, $value, $e)
    {
        Log::error("IMPORT FIELD ERROR", [
            'row'   => $rowIndex,
            'field' => $field,
            'value' => $value,
            'error' => $e->getMessage(),
        ]);
    }

    protected function logRowError($rowIndex, $message, $row)
    {
        Log::error("IMPORT ROW ERROR", [
            'row'   => $rowIndex,
            'error' => $message,
            'data'  => $row,
        ]);
    }
}