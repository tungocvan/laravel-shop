<?php

namespace Modules\Pharma\Services;

use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PriceListExcelTypography
{
    public const FONT_FAMILY = 'Times New Roman';
    public const DEFAULT_FONT_SIZE = 12;
    public const PRODUCT_FONT_SIZES = [10, 11, 12, 13];
    public const DEFAULT_HEADER_BACKGROUND = 'E8EEF9';
    public const DEFAULT_HEADER_TEXT = '111827';

    public function productFontSize(array $pageSetup): int
    {
        $size = (int) ($pageSetup['product_font_size'] ?? self::DEFAULT_FONT_SIZE);

        return in_array($size, self::PRODUCT_FONT_SIZES, true) ? $size : self::DEFAULT_FONT_SIZE;
    }

    public function color(array $pageSetup, string $key, string $fallback): string
    {
        $color = strtoupper(ltrim((string) ($pageSetup[$key] ?? $fallback), '#'));

        return preg_match('/^[0-9A-F]{6}$/', $color) === 1 ? $color : $fallback;
    }

    public function apply(Worksheet $sheet, int $headerRow, int $lastDataRow, array $pageSetup): void
    {
        $highestColumn = $sheet->getHighestColumn();
        $tableLastRow = max($headerRow, $lastDataRow);
        $headerRange = "A{$headerRow}:{$highestColumn}{$headerRow}";
        $tableRange = "A{$headerRow}:{$highestColumn}{$tableLastRow}";

        $sheet->getParent()->getDefaultStyle()->getFont()
            ->setName(self::FONT_FAMILY)
            ->setSize(self::DEFAULT_FONT_SIZE);

        // Explicitly stamp the current used range so Excel never falls back to Aptos/Calibri.
        $sheet->getStyle('A1:'.$highestColumn.$sheet->getHighestRow())->getFont()->setName(self::FONT_FAMILY);

        $sheet->getStyle($headerRange)->getFont()
            ->setName(self::FONT_FAMILY)
            ->setSize(self::DEFAULT_FONT_SIZE)
            ->setBold(true)
            ->getColor()->setRGB($this->color($pageSetup, 'header_text_color', self::DEFAULT_HEADER_TEXT));

        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal('center')
            ->setVertical('center')
            ->setWrapText(true);

        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($this->color($pageSetup, 'header_background', self::DEFAULT_HEADER_BACKGROUND));

        if (($pageSetup['table_border'] ?? 'thin') === 'thin') {
            $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        if ($lastDataRow > $headerRow) {
            $sheet->getStyle('A'.($headerRow + 1).":{$highestColumn}{$lastDataRow}")
                ->getFont()
                ->setName(self::FONT_FAMILY)
                ->setSize($this->productFontSize($pageSetup));
        }
    }
}
