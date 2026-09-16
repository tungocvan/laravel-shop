<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as DrawingMetrics;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PriceListExcelDocumentLayout
{
    private const DEFAULT_LOGO_WIDTH_CM = 4.65;
    private const DEFAULT_LOGO_HEIGHT_CM = 2.82;
    private const DEFAULT_SIGNATURE_WIDTH_CM = 4.00;
    private const DEFAULT_SIGNATURE_HEIGHT_CM = 3.60;

    public function header(Worksheet $sheet, array $profile, int $columnCount, int $startRow = 1): int
    {
        $hf = $profile['header_footer'] ?? [];
        if (! (bool) ($hf['enabled'] ?? true)) {
            return $startRow;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(max(1, $columnCount));
        $logoEndIndex = min(3, max(1, $columnCount));
        $logoEnd = Coordinate::stringFromColumnIndex($logoEndIndex);
        $infoStartIndex = min(4, max(1, $columnCount));
        $infoStart = Coordinate::stringFromColumnIndex($infoStartIndex);
        $row = $startRow;

        $sheet->mergeCells("A{$row}:{$logoEnd}".($row + 3));
        $this->addDrawing($sheet, $profile['logo_path'] ?? null, 'Logo', "A{$row}", $this->dimension($hf, 'logo_width_cm', self::DEFAULT_LOGO_WIDTH_CM, 1, 12), $this->dimension($hf, 'logo_height_cm', self::DEFAULT_LOGO_HEIGHT_CM, 1, 8));

        $companyRows = [
            [(string) ($hf['company_name'] ?? ''), true],
            [($hf['address'] ?? '') !== '' ? 'Địa chỉ: '.$hf['address'] : '', false],
            [($hf['tax_code'] ?? '') !== '' ? 'Mã số thuế: '.$hf['tax_code'] : '', false],
            [$this->contactLine($hf), false],
        ];
        foreach ($companyRows as $offset => [$value, $bold]) {
            $currentRow = $row + $offset;
            $sheet->mergeCells("{$infoStart}{$currentRow}:{$lastColumn}{$currentRow}");
            $sheet->setCellValue("{$infoStart}{$currentRow}", $value);
            $sheet->getStyle("{$infoStart}{$currentRow}:{$lastColumn}{$currentRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            if ($bold) {
                $sheet->getStyle("{$infoStart}{$currentRow}")->getFont()->setBold(true)->setSize(12);
            }
        }

        $logoHeightCm = $this->dimension($hf, 'logo_height_cm', self::DEFAULT_LOGO_HEIGHT_CM, 1, 8);
        $headerRowHeight = max(20, ($logoHeightCm * 28.35) / 4);
        foreach (range($row, $row + 3) as $headerRow) {
            $sheet->getRowDimension($headerRow)->setRowHeight($headerRowHeight);
        }

        $row += 5;
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->setCellValue("A{$row}", $hf['title'] ?? 'BẢNG BÁO GIÁ');
        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(26);

        $row++;
        if (($hf['recipient'] ?? '') !== '') {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->setCellValue("A{$row}", 'Kính gửi: '.$hf['recipient']);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }
        if (($hf['intro'] ?? '') !== '') {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->setCellValue("A{$row}", $hf['intro']);
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($row)->setRowHeight(30);
            $row++;
        }

        return $row;
    }

    public function footer(Worksheet $sheet, array $profile, int $columnCount, int $afterRow): void
    {
        $hf = $profile['header_footer'] ?? [];
        if (! (bool) ($hf['enabled'] ?? true)) {
            return;
        }

        $lastIndex = max(1, $columnCount);
        $firstIndex = max(1, $lastIndex - 4);
        $first = Coordinate::stringFromColumnIndex($firstIndex);
        $last = Coordinate::stringFromColumnIndex($lastIndex);
        $row = $afterRow + 2;
        $location = trim((string) ($hf['footer_location'] ?? ''));
        $year = trim((string) ($hf['footer_year'] ?? ''));
        $locationLine = $location;
        if ($year !== '') {
            $locationLine .= ($locationLine !== '' ? ', ' : '').'ngày.....tháng.....năm '.$year;
        }

        $this->mergedFooterCell($sheet, $first, $last, $row, $locationLine, false, true);
        $this->mergedFooterCell($sheet, $first, $last, ++$row, (string) ($hf['signatory_title'] ?? ''), true, false);

        $signatureRow = ++$row;
        $signatureEndRow = $signatureRow + 2;
        $sheet->mergeCells("{$first}{$signatureRow}:{$last}{$signatureEndRow}");
        $signatureWidthCm = $this->dimension($hf, 'signature_width_cm', self::DEFAULT_SIGNATURE_WIDTH_CM, 1, 12);
        $signatureHeightCm = $this->dimension($hf, 'signature_height_cm', self::DEFAULT_SIGNATURE_HEIGHT_CM, 1, 8);
        [$anchorColumn, $offsetX] = $this->centeredDrawingAnchor($sheet, $firstIndex, $lastIndex, $signatureWidthCm);
        $this->addDrawing($sheet, $profile['signature_path'] ?? null, 'Signature', "{$anchorColumn}{$signatureRow}", $signatureWidthCm, $signatureHeightCm, $offsetX);

        $signatureRowHeight = max(30, ($signatureHeightCm * 28.35) / 3);
        foreach (range($signatureRow, $signatureEndRow) as $imageRow) {
            $sheet->getRowDimension($imageRow)->setRowHeight($signatureRowHeight);
        }
        $this->mergedFooterCell($sheet, $first, $last, $signatureEndRow + 1, (string) ($hf['signatory_name'] ?? ''), true, false);
    }

    private function mergedFooterCell(Worksheet $sheet, string $first, string $last, int $row, string $value, bool $bold, bool $italic): void
    {
        $sheet->mergeCells("{$first}{$row}:{$last}{$row}");
        $sheet->setCellValue("{$first}{$row}", $value);
        $style = $sheet->getStyle("{$first}{$row}:{$last}{$row}");
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $style->getFont()->setBold($bold)->setItalic($italic);
    }

    private function contactLine(array $hf): string
    {
        $parts = [];
        if (($hf['phone'] ?? '') !== '') $parts[] = 'Số điện thoại: '.$hf['phone'];
        if (($hf['email'] ?? '') !== '') $parts[] = 'Email: '.$hf['email'];
        return implode('     ', $parts);
    }

    private function dimension(array $hf, string $key, float $default, float $min, float $max): float
    {
        $value = (float) ($hf[$key] ?? $default);
        return max($min, min($max, $value > 0 ? $value : $default));
    }

    /** @return array{0:string,1:int} */
    private function centeredDrawingAnchor(Worksheet $sheet, int $firstIndex, int $lastIndex, float $drawingWidthCm): array
    {
        $widths = [];
        $regionPixels = 0;
        for ($index = $firstIndex; $index <= $lastIndex; $index++) {
            $column = Coordinate::stringFromColumnIndex($index);
            $width = (float) $sheet->getColumnDimension($column)->getWidth();
            if ($width <= 0) {
                $width = (float) $sheet->getDefaultColumnDimension()->getWidth();
            }
            if ($width <= 0) {
                $width = 8.43;
            }
            $pixels = DrawingMetrics::cellDimensionToPixels($width, $sheet->getParent()?->getDefaultStyle()->getFont());
            $widths[$index] = $pixels;
            $regionPixels += $pixels;
        }

        $drawingPixels = (int) round($drawingWidthCm * 37.795);
        $targetLeft = max(0, (int) round(($regionPixels - $drawingPixels) / 2));
        $consumed = 0;
        foreach ($widths as $index => $pixels) {
            if ($targetLeft < $consumed + $pixels) {
                return [Coordinate::stringFromColumnIndex($index), max(0, $targetLeft - $consumed)];
            }
            $consumed += $pixels;
        }

        return [Coordinate::stringFromColumnIndex($firstIndex), 0];
    }

    private function addDrawing(Worksheet $sheet, ?string $path, string $name, string $coordinate, float $widthCm, float $heightCm, int $offsetX = 0): void
    {
        if (! $path || ! Storage::disk('public')->exists($path)) return;
        $drawing = new Drawing;
        $drawing->setName($name);
        $drawing->setPath(Storage::disk('public')->path($path));
        $drawing->setCoordinates($coordinate);
        $drawing->setWidth(max(20, (int) round($widthCm * 37.795)));
        $drawing->setHeight(max(20, (int) round($heightCm * 37.795)));
        $drawing->setOffsetX($offsetX);
        $drawing->setWorksheet($sheet);
    }
}
