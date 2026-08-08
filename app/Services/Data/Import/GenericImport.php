<?php

namespace App\Services\Data\Import;

use App\Services\Data\DataTransformer;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Maatwebsite\Excel\Concerns\{
    ToCollection,
    WithHeadingRow,
    SkipsEmptyRows
};

class GenericImport implements
    ToCollection,
    WithHeadingRow,
    SkipsEmptyRows
{
    protected DataTransformer $transformer;

    protected string $modelClass;

    /**
     * 🔑 Unique fields để update
     */
    protected array $uniqueBy = [];

    /**
     * ❌ Fields không import
     */
    protected array $exceptFields = [];

    public function __construct(
        string $modelClass,
        array $uniqueBy = ['id'],
        array $exceptFields = ['id', 'created_at', 'updated_at', 'deleted_at']
    ) {
        $this->transformer = app(DataTransformer::class);
        $this->modelClass = $modelClass;
        $this->uniqueBy = $uniqueBy;
        $this->exceptFields = $exceptFields;
    }

    /**
     * =========================
     * 🚀 MAIN
     * =========================
     */
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            $model = new $this->modelClass;

            foreach ($rows as $index => $row) {

                $rowIndex = $index + 2;

                try {

                    /**
                     * 1. Normalize
                     */
                    $row = $this->normalizeRow($row->toArray());

                    /**
                     * 2. Remove except fields
                     */
                    foreach ($this->exceptFields as $field) {
                        unset($row[$field]);
                    }

                    /**
                     * 3. Transform data (🔥 CORE)
                     */
                    $data = $this->transformer->transformInput($model, $row);

                    if (empty($data)) continue;

                    /**
                     * 4. Resolve key
                     */
                    $query = $this->modelClass::query();

                    $hasKey = false;

                    foreach ($this->uniqueBy as $field) {
                        if (!empty($data[$field])) {
                            $query->orWhere($field, $data[$field]);
                            $hasKey = true;
                        }
                    }

                    if (!$hasKey) {
                        $this->logRowError($rowIndex, 'missing_unique_key', $row);
                        continue;
                    }

                    /**
                     * 5. Upsert
                     */
                    $record = $query->first();

                    if ($record) {
                        $record->update($data);
                    } else {
                        //dd($data);
                        $this->modelClass::create($data);
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
     * 🔤 NORMALIZE COLUMN
     * =========================
     */
    protected function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {

            $column = Str::of($key)
                ->lower()
                ->replace([' ', '-', '.'], '_')
                ->toString();

            $normalized[$column] = $value;
        }

        return $normalized;
    }

    /**
     * =========================
     * 🚨 LOG FIELD ERROR
     * =========================
     */
    protected function logFieldError($rowIndex, $field, $value, $message)
    {
        Log::error("IMPORT FIELD ERROR", [
            'row'   => $rowIndex,
            'field' => $field,
            'value' => $value,
            'error' => $message,
        ]);
    }

    /**
     * =========================
     * 🚨 LOG ROW ERROR
     * =========================
     */
    protected function logRowError($rowIndex, $message, $row)
    {
        Log::error("IMPORT ROW ERROR", [
            'row'   => $rowIndex,
            'error' => $message,
            'data'  => $row,
        ]);
    }
}