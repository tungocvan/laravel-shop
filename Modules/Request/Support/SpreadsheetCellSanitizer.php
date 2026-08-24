<?php

namespace Modules\Request\Support;

final class SpreadsheetCellSanitizer
{
    public function sanitize(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/u', $value) === 1
            ? "'".$value
            : $value;
    }

    public function sanitizeRow(array $row): array
    {
        return array_map(fn (mixed $value): mixed => $this->sanitize($value), $row);
    }
}
