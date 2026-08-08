<?php

namespace App\Services\Data;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TypeCaster
{
    /**
     * =========================
     * INPUT (IMPORT / API)
     * =========================
     */
    public function castInput(?string $type, $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!$type) {
            return $this->cleanPrimitive($value);
        }

        $baseType = explode(':', $type)[0];

        return match ($baseType) {

            'date', 'datetime' => $this->parseDate($value),

            'array', 'json'    => $this->parseArray($value),

            'boolean'          => $this->parseBoolean($value),

            'int', 'integer'   => (int) $value,

            'float', 'double'  => (float) $value,

            default            => $this->cleanPrimitive($value),
        };
    }

    /**
     * =========================
     * OUTPUT (EXPORT / API)
     * =========================
     */
    public function castOutput(?string $type, $value)
    {
        if ($value === null) {
            return '';
        }

        $baseType = $type ? explode(':', $type)[0] : null;

        return match ($baseType) {

            'date', 'datetime' => $this->formatDate($value),

            'array', 'json'    => is_array($value)
                ? implode(', ', $value)
                : $value,

            'boolean'          => $value ? 'Yes' : 'No',

            default            => $value,
        };
    }

    /**
     * =========================
     * 📅 DATE PARSER (STRICT + SAFE)
     * =========================
     */
    protected function parseDate($value)
    {
        try {
            // 1. Excel serial (45123)
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            }

            $value = trim((string) $value);

            // 2. Year only (1998)
            if (preg_match('/^\d{4}$/', $value)) {
                return $value . '-01-01';
            }

            // 3. Known formats (strict)
            $formats = [
                'Y-m-d',
                'd/m/Y',
                'd-m-Y',
                'd/m/y',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)
                        ->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }

            throw new \Exception();

        } catch (\Exception $e) {
            throw new \Exception("Invalid date format: {$value}");
        }
    }

    /**
     * =========================
     * 📦 ARRAY / JSON (CORE FIX)
     * =========================
     */
    protected function parseArray($value): array
    {
        // ✅ Already array
        if (is_array($value)) {
            return $value;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        // ✅ JSON string
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return is_array($decoded) ? $decoded : [$decoded];
        }

        // ✅ CSV string → "A, B, C"
        return collect(explode(',', $value))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * =========================
     * 🔘 BOOLEAN (STRICT)
     * =========================
     */
    protected function parseBoolean($value): bool
    {
        $v = strtolower(trim((string) $value));

        return match ($v) {
            '1', 'true', 'yes', 'y', 'x' => true,
            '0', 'false', 'no', 'n', ''  => false,
            default => throw new \Exception("Invalid boolean: {$value}"),
        };
    }

    /**
     * =========================
     * 📤 FORMAT DATE OUTPUT
     * =========================
     */
    protected function formatDate($value): string
    {
        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Exception $e) {
            return (string) $value;
        }
    }

    /**
     * =========================
     * 🧹 CLEAN STRING
     * =========================
     */
    protected function cleanPrimitive($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return trim(
            preg_replace('/[\x00-\x1F\x7F]/u', '', $value)
        );
    }
}
