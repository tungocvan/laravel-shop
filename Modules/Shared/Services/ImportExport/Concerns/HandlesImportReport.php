<?php

namespace Modules\Shared\Services\ImportExport\Concerns;

trait HandlesImportReport
{
    protected int $totalRows = 0;

    protected int $successRows = 0;

    protected int $errorRows = 0;

    protected int $skippedRows = 0;

    protected array $errors = [];

    protected array $debug = [];

    protected function resetReport(): void
    {
        $this->totalRows = 0;
        $this->successRows = 0;
        $this->errorRows = 0;
        $this->skippedRows = 0;
        $this->errors = [];
        $this->debug = [];
    }

    protected function addError(
        string $sheet,
        ?int $row,
        ?string $column,
        string $reason,
        mixed $value = null
    ): void {
        $this->errorRows++;

        $this->errors[] = [
            'sheet' => $sheet,
            'row' => $row,
            'column' => $column,
            'value' => $value,
            'reason' => $reason,
        ];
    }

    protected function addDebug(string $key, mixed $value): void
    {
        $this->debug[$key] = $value;
    }

    protected function report(bool $success): array
    {
        return [
            'success' => $success,
            'total_rows' => $this->totalRows,
            'success_rows' => $this->successRows,
            'error_rows' => $this->errorRows,
            'skipped_rows' => $this->skippedRows,
            'errors' => $this->errors,
            'debug' => $this->debug,
        ];
    }
}
