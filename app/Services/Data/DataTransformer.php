<?php
namespace App\Services\Data;


class DataTransformer
{
    public function __construct(
        protected TypeCaster $caster,
        protected ValueCleaner $cleaner
    ) {}

    /**
     * =========================
     * INPUT (IMPORT / API)
     * =========================
     */
    public function transformInput($model, array $row): array
    {
        $casts = $model->getCasts();
        $data = [];

        foreach ($row as $column => $value) {

            if ($value === null || $value === '') {
                continue;
            }

            // 🧹 clean trước
            $value = $this->cleaner->clean($value);

            // 🔄 cast theo model
            $data[$column] = $this->caster->castInput(
                $casts[$column] ?? null,
                $value
            );

        }

        return $data;
    }

    /**
     * =========================
     * OUTPUT (EXPORT / API)
     * =========================
     */
    public function transformOutput($model, array $row): array
    {
        $casts = $model->getCasts();
        $data = [];

        foreach ($row as $column => $value) {

            $data[$column] = $this->caster->castOutput(
                $casts[$column] ?? null,
                $value
            );
        }


        return $data;
    }
}