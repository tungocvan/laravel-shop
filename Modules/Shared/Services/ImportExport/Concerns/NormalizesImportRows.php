<?php

namespace Modules\Shared\Services\ImportExport\Concerns;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

trait NormalizesImportRows
{
    protected function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function cleanNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);
        $value = str_replace([' ', ','], ['', ''], $value);

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function cleanInteger(mixed $value): ?int
    {
        $number = $this->cleanNumber($value);

        return $number === null ? null : (int) $number;
    }

    protected function cleanBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = mb_strtolower(trim((string) $value));

        return match ($value) {
            '1', 'true', 'yes', 'y', 'co', 'có', 'active', 'hoat_dong' => true,
            '0', 'false', 'no', 'n', 'khong', 'không', 'inactive', 'ngung' => false,
            default => null,
        };
    }

    protected function cleanDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance(\DateTimeImmutable::createFromInterface($value))->format('Y-m-d');
            }

            if (is_numeric($value)) {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((float) $value)
                )->format('Y-m-d');
            }

            $value = trim((string) $value);

            foreach (['!d/m/Y', '!j/n/Y', '!d-m-Y', '!j-n-Y', '!Y-m-d'] as $format) {
                $date = Carbon::createFromFormat($format, $value);
                $errors = Carbon::getLastErrors();

                if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                    return $date->format('Y-m-d');
                }
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function value(array $row, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $row) ? $row[$key] : $default;
    }
}
