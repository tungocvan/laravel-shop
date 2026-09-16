<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\PriceListExcelTypography;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListExcelTypographyContractTest extends TestCase
{
    #[Test]
    public function typography_uses_times_new_roman_and_supported_product_sizes(): void
    {
        $typography = new PriceListExcelTypography;

        $this->assertSame('Times New Roman', PriceListExcelTypography::FONT_FAMILY);
        $this->assertSame(12, PriceListExcelTypography::DEFAULT_FONT_SIZE);
        $this->assertSame([10, 11, 12, 13], PriceListExcelTypography::PRODUCT_FONT_SIZES);
        $this->assertSame(12, $typography->productFontSize([]));
        $this->assertSame(10, $typography->productFontSize(['product_font_size' => 10]));
        $this->assertSame(13, $typography->productFontSize(['product_font_size' => 13]));
        $this->assertSame(12, $typography->productFontSize(['product_font_size' => 14]));
    }

    #[Test]
    public function typography_applies_header_and_product_row_styles(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Tên thuốc', 'Hoạt chất'],
            ['Thuốc A', 'Hoạt chất A'],
        ], null, 'A1');

        (new PriceListExcelTypography)->apply($sheet, 1, 2, ['product_font_size' => 11]);

        $this->assertSame('Times New Roman', $spreadsheet->getDefaultStyle()->getFont()->getName());
        $this->assertSame(12.0, $spreadsheet->getDefaultStyle()->getFont()->getSize());
        $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
        $this->assertSame(12.0, $sheet->getStyle('A1')->getFont()->getSize());
        $this->assertSame('center', $sheet->getStyle('A1')->getAlignment()->getHorizontal());
        $this->assertSame('center', $sheet->getStyle('A1')->getAlignment()->getVertical());
        $this->assertTrue($sheet->getStyle('A1')->getAlignment()->getWrapText());
        $this->assertSame(11.0, $sheet->getStyle('A2')->getFont()->getSize());
    }
}
