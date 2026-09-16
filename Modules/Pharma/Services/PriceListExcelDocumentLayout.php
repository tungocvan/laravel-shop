<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PriceListExcelDocumentLayout
{
    public function header(Worksheet $sheet, array $profile, int $columnCount, int $startRow = 1): int
    {
        $hf = $profile['header_footer'] ?? [];
        if (! (bool) ($hf['enabled'] ?? true)) {
            return $startRow;
        }

        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $infoStartIndex = $columnCount >= 4 ? 3 : min(2, $columnCount);
        $infoStart = Coordinate::stringFromColumnIndex($infoStartIndex);
        $logoEnd = Coordinate::stringFromColumnIndex(max(1, $infoStartIndex - 1));
        $row = $startRow;

        $sheet->mergeCells("A{$row}:{$logoEnd}".($row + 3));
        $this->addDrawing(
            $sheet,
            $profile['logo_path'] ?? null,
            'Logo',
            "A{$row}",
            (float) ($hf['logo_width_cm'] ?? 2.48),
            (float) ($hf['logo_height_cm'] ?? 3.83)
        );

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
            $sheet->getStyle("{$infoStart}{$currentRow}:{$lastColumn}{$currentRow}")->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            if ($bold) {
                $sheet->getStyle("{$infoStart}{$currentRow}")->getFont()->setBold(true)->setSize(12);
            }
        }

        foreach (range($row, $row + 3) as $headerRow) {
            $sheet->getRowDimension($headerRow)->setRowHeight(20);
        }
        if (! empty($profile['logo_path'])) {
            $sheet->getRowDimension($row)->setRowHeight(24);
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
        $this->addDrawing(
            $sheet,
            $profile['signature_path'] ?? null,
            'Signature',
            "{$first}{$signatureRow}",
            (float) ($hf['signature_width_cm'] ?? 4),
            (float) ($hf['signature_height_cm'] ?? 2),
            true
        );
        $sheet->getRowDimension($signatureRow)->setRowHeight(max(30, (float) ($hf['signature_height_cm'] ?? 2) * 28.35));

        $nameRow = $signatureEndRow + 1;
        $this->mergedFooterCell($sheet, $first, $last, $nameRow, (string) ($hf['signatory_name'] ?? ''), true, false);
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
        if (($hf['phone'] ?? '') !== '') {
            $parts[] = 'Số điện thoại: '.$hf['phone'];
        }
        if (($hf['email'] ?? '') !== '') {
            $parts[] = 'Email: '.$hf['email'];
        }

        return implode('     ', $parts);
    }

    private function addDrawing(Worksheet $sheet, ?string $path, string $name, string $coordinate, float $widthCm, float $heightCm, bool $center = false): void
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return;
        }

        $drawing = new Drawing;
        $drawing->setName($name);
        $drawing->setPath(Storage::disk('public')->path($path));
        $drawing->setCoordinates($coordinate);
        $drawing->setWidth(max(20, (int) round($widthCm * 37.795)));
        $drawing->setHeight(max(20, (int) round($heightCm * 37.795)));
        if ($center) {
            $drawing->setOffsetX(12);
        }
        $drawing->setWorksheet($sheet);
    }
}
