<?php

namespace Modules\Pharma\Services\Spreadsheet;

use Modules\Pharma\DTOs\WorkbookAnalysis;
use Modules\Pharma\Exceptions\AmbiguousHeaderException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class WorkbookAnalyzer
{
    public function analyze(string $filePath, ?string $sheetName = null): WorkbookAnalysis
    {
        $this->assertReadableWorkbook($filePath);

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $sheetName ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getActiveSheet();

        if (! $sheet) {
            throw new RuntimeException("Không tìm thấy sheet: {$sheetName}");
        }

        $headerRow = $this->findHeaderRow($sheet);
        $lastColumnIndex = $this->findLastHeaderColumnIndex($sheet, $headerRow);
        [$columns, $duplicates] = $this->readColumns($sheet, $headerRow, $lastColumnIndex);

        if ($duplicates !== []) {
            throw new AmbiguousHeaderException($duplicates);
        }

        $sttColumn = collect($columns)->firstWhere('normalized', 'stt');

        if (! $sttColumn) {
            throw new RuntimeException('Không tìm thấy cột STT hợp lệ.');
        }

        $products = $this->readProducts($sheet, $headerRow, $sttColumn['index'], $columns);

        return new WorkbookAnalysis(
            sheetName: $sheet->getTitle(),
            headerRow: $headerRow,
            lastHeaderColumn: Coordinate::stringFromColumnIndex($lastColumnIndex),
            columns: $columns,
            products: $products,
            duplicateHeaders: $duplicates,
        );
    }

    public function normalizeHeader(mixed $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', (string) $value);
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return mb_strtolower($value);
    }

    private function findHeaderRow(Worksheet $sheet): int
    {
        for ($row = 1; $row <= $sheet->getHighestDataRow(); $row++) {
            for ($column = 1; $column <= 50; $column++) {
                if ($this->normalizeHeader($sheet->getCell([$column, $row])->getValue()) === 'stt') {
                    return $row;
                }
            }
        }

        throw new RuntimeException('Không xác định được dòng tiêu đề chứa STT.');
    }

    private function findLastHeaderColumnIndex(Worksheet $sheet, int $headerRow): int
    {
        $last = 0;

        for ($column = 1; $column <= 200; $column++) {
            if ($this->normalizeHeader($sheet->getCell([$column, $headerRow])->getValue()) !== '') {
                $last = $column;
            }
        }

        if ($last === 0) {
            throw new RuntimeException('Dòng tiêu đề không có nội dung.');
        }

        return $last;
    }

    private function readColumns(Worksheet $sheet, int $headerRow, int $lastColumnIndex): array
    {
        $columns = [];
        $byNormalizedHeader = [];

        for ($index = 1; $index <= $lastColumnIndex; $index++) {
            $letter = Coordinate::stringFromColumnIndex($index);
            $header = $this->displayValue($sheet->getCell([$index, $headerRow])->getValue());
            $normalized = $this->normalizeHeader($header);

            if ($normalized === '') {
                continue;
            }

            $columns[] = compact('letter', 'index', 'header', 'normalized');
            $byNormalizedHeader[$normalized][] = $letter;
        }

        $duplicates = array_filter($byNormalizedHeader, static fn (array $letters): bool => count($letters) > 1);

        return [$columns, $duplicates];
    }

    private function readProducts(Worksheet $sheet, int $headerRow, int $sttColumnIndex, array $columns): array
    {
        $products = [];

        for ($row = $headerRow + 1; $row <= $sheet->getHighestDataRow(); $row++) {
            $stt = $this->validInteger($sheet->getCell([$sttColumnIndex, $row])->getValue());

            if ($stt === null) {
                continue;
            }

            $values = [];
            foreach ($columns as $column) {
                $values[$column['letter']] = $sheet->getCell([$column['index'], $row])->getFormattedValue();
            }

            $products[] = [
                'row' => $row,
                'stt' => $stt,
                'name' => $this->productName($columns, $values),
                'active_ingredient' => $this->valueByHeader($columns, $values, ['tên hoạt chất']),
                'registration_number' => $this->valueByHeader($columns, $values, ['giấy phép lưu hành sản phẩm']),
                'values' => $values,
            ];
        }

        return $products;
    }

    private function productName(array $columns, array $values): string
    {
        return $this->valueByHeader($columns, $values, ['tên biệt dược', 'tên thuốc', 'tên sản phẩm']) ?: '-';
    }

    private function valueByHeader(array $columns, array $values, array $headers): string
    {
        foreach ($columns as $column) {
            if (in_array($column['normalized'], $headers, true)) {
                return (string) ($values[$column['letter']] ?? '');
            }
        }

        return '';
    }

    private function validInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        $value = trim((string) $value);

        return preg_match('/^[+-]?\d+$/', $value) === 1 ? (int) $value : null;
    }

    private function displayValue(mixed $value): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
    }

    private function assertReadableWorkbook(string $filePath): void
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException('File Excel nguồn không tồn tại hoặc không đọc được.');
        }

        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new RuntimeException('Chỉ hỗ trợ workbook XLSX trong giai đoạn này.');
        }
    }
}
