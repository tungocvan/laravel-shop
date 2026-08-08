<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Str;
use Modules\Pharma\DTOs\PriceListOptions;
use Modules\Pharma\DTOs\WorkbookAnalysis;
use Modules\Pharma\Services\Spreadsheet\PriceListWorkbookBuilder;
use Modules\Pharma\Services\Spreadsheet\WorkbookAnalyzer;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use RuntimeException;

class PriceListService
{
    public const DEFAULT_SOURCE = 'excel/BANG_GIA_TONG_HOP.xlsx';

    public function __construct(
        private readonly WorkbookAnalyzer $analyzer,
        private readonly PriceListWorkbookBuilder $builder,
    ) {}

    public function analyze(?string $sheetName = null): WorkbookAnalysis
    {
        return $this->analyzer->analyze($this->sourcePath(), $sheetName);
    }

    public function filteredProducts(WorkbookAnalysis $analysis, string $search = ''): array
    {
        $search = mb_strtolower(trim($search));

        if ($search === '') {
            return $analysis->products;
        }

        if (ctype_digit($search)) {
            return array_values(array_filter(
                $analysis->products,
                static fn (array $product): bool => $product['stt'] === (int) $search
            ));
        }

        return array_values(array_filter($analysis->products, static function (array $product) use ($search): bool {
            $haystack = mb_strtolower(implode(' ', [
                $product['stt'],
                $product['name'],
                $product['active_ingredient'],
                $product['registration_number'],
            ]));

            return str_contains($haystack, $search);
        }));
    }

    public function parseColumns(string $expression, WorkbookAnalysis $analysis): array
    {
        $expression = strtoupper(preg_replace('/\s+/', '', $expression) ?? '');
        if ($expression === '') {
            throw new RuntimeException('Vui lòng chọn ít nhất một cột.');
        }

        $allowed = array_column($analysis->columns, 'letter');
        $selected = [];

        foreach (explode(',', $expression) as $part) {
            if (preg_match('/^([A-Z]+):([A-Z]+)$/', $part, $matches)) {
                $start = Coordinate::columnIndexFromString($matches[1]);
                $end = Coordinate::columnIndexFromString($matches[2]);
                if ($start > $end) {
                    throw new RuntimeException("Khoảng cột không hợp lệ: {$part}");
                }
                for ($index = $start; $index <= $end; $index++) {
                    $selected[] = Coordinate::stringFromColumnIndex($index);
                }
            } elseif (preg_match('/^[A-Z]+$/', $part)) {
                $selected[] = $part;
            } else {
                throw new RuntimeException("Cú pháp cột không hợp lệ: {$part}");
            }
        }

        $selected = array_values(array_unique($selected));
        $invalid = array_values(array_diff($selected, $allowed));

        if ($invalid !== []) {
            throw new RuntimeException('Cột không có tiêu đề hoặc nằm ngoài bảng: '.implode(', ', $invalid));
        }

        return $selected;
    }

    public function generate(array $input): string
    {
        $analysis = $this->analyze($input['sheet_name'] ?? null);
        $columns = $this->parseColumns((string) ($input['columns'] ?? ''), $analysis);
        $productRows = $this->resolveProductRows($analysis, $input['product_rows'] ?? []);

        if ($productRows === []) {
            throw new RuntimeException('Vui lòng chọn ít nhất một sản phẩm.');
        }

        $outputPath = ! empty($input['output_path'])
            ? (string) $input['output_path']
            : storage_path(
                'app/private/exports/price-lists/bang-gia-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.xlsx'
            );

        $options = new PriceListOptions(
            productRows: $productRows,
            sourceColumns: $columns,
            recipient: trim((string) ($input['recipient'] ?? 'QUÝ KHÁCH HÀNG')),
            signatureDate: trim((string) ($input['signature_date'] ?: 'Tp.HCM, ngày….tháng…...năm '.now()->year)),
            signatureTitle: trim((string) ($input['signature_title'] ?? 'GIÁM ĐỐC CÔNG TY')),
            outputPath: $outputPath,
        );

        return $this->builder->build(
            $this->sourcePath(),
            $analysis->sheetName,
            $analysis->headerRow,
            $options,
        );
    }

    private function resolveProductRows(WorkbookAnalysis $analysis, array $rows): array
    {
        $validRows = array_column($analysis->products, 'row');
        $rows = array_values(array_unique(array_map('intval', $rows)));
        $invalid = array_values(array_diff($rows, $validRows));

        if ($invalid !== []) {
            throw new RuntimeException('Có dòng sản phẩm không hợp lệ: '.implode(', ', $invalid));
        }

        return array_values(array_filter($validRows, static fn (int $row): bool => in_array($row, $rows, true)));
    }

    private function sourcePath(): string
    {
        return storage_path('app/'.self::DEFAULT_SOURCE);
    }
}
