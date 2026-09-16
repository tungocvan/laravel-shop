<?php

namespace Modules\Pharma\Services;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PriceListExcelTypography
{
    public const FONT_FAMILY = 'Times New Roman';

    public const DEFAULT_FONT_SIZE = 12;

    public const PRODUCT_FONT_SIZES = [10, 11, 12, 13];

    public function productFontSize(array $pageSetup): int
    {
        $size = (int) ($pageSetup['product_font_size'] ?? self::DEFAULT_FONT_SIZE);

        return in_array($size, self::PRODUCT_FONT_SIZES, true)
            ? $size
            : self::DEFAULT_FONT_SIZE;
    }

    public function apply(Worksheet $sheet, int $headerRow, int $lastDataRow, array $pageSetup): void
    {
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getParent()
            ->getDefaultStyle()
            ->getFont()
            ->setName(self::FONT_FAMILY)
            ->setSize(self::DEFAULT_FONT_SIZE);

        $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
            ->getFont()
            ->setName(self::FONT_FAMILY)
            ->setSize(self::DEFAULT_FONT_SIZE)
            ->setBold(true);

        $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
            ->getAlignment()
            ->setHorizontal('center')
            ->setVertical('center')
            ->setWrapText(true);

        if ($lastDataRow <= $headerRow) {
            return;
        }

        $sheet->getStyle('A'.($headerRow + 1).":{$highestColumn}{$lastDataRow}")
            ->getFont()
            ->setName(self::FONT_FAMILY)
            ->setSize($this->productFontSize($pageSetup));
    }
}
