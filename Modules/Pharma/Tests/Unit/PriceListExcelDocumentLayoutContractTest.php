<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\PriceListExcelDocumentLayout;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class PriceListExcelDocumentLayoutContractTest extends TestCase
{
    public function test_header_uses_company_identity_and_footer_merges_last_five_columns(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $profile = [
            'header_footer' => [
                'enabled' => true,
                'company_name' => 'CÔNG TY TEST',
                'address' => '123 Đường Test',
                'tax_code' => '0312345678',
                'phone' => '0900000000',
                'email' => 'test@example.com',
                'title' => 'BẢNG CHÀO GIÁ',
                'recipient' => 'QUÝ KHÁCH HÀNG',
                'intro' => 'Nội dung giới thiệu',
                'footer_location' => 'Tp.HCM',
                'footer_year' => '2026',
                'signatory_title' => 'GIÁM ĐỐC CÔNG TY',
                'signatory_name' => 'NGUYỄN VĂN A',
            ],
            'logo_path' => null,
            'signature_path' => null,
        ];

        $layout = app(PriceListExcelDocumentLayout::class);
        $nextRow = $layout->header($sheet, $profile, 14);

        $this->assertSame('CÔNG TY TEST', $sheet->getCell('D1')->getValue());
        $this->assertSame('Địa chỉ: 123 Đường Test', $sheet->getCell('D2')->getValue());
        $this->assertSame('Mã số thuế: 0312345678', $sheet->getCell('D3')->getValue());
        $this->assertContains('A1:C4', $sheet->getMergeCells());
        $this->assertSame('BẢNG CHÀO GIÁ', $sheet->getCell('A6')->getValue());
        $this->assertGreaterThan(6, $nextRow);

        $layout->footer($sheet, $profile, 14, 20);
        $merged = $sheet->getMergeCells();

        $this->assertContains('J22:N22', $merged);
        $this->assertContains('J23:N23', $merged);
        $this->assertContains('J24:N26', $merged);
        $this->assertContains('J27:N27', $merged);
        $this->assertSame('GIÁM ĐỐC CÔNG TY', $sheet->getCell('J23')->getValue());
        $this->assertSame('NGUYỄN VĂN A', $sheet->getCell('J27')->getValue());
    }

    public function test_layout_has_requested_media_defaults_and_supports_custom_dimensions(): void
    {
        $layout = file_get_contents(base_path('Modules/Pharma/Services/PriceListExcelDocumentLayout.php'));

        foreach (['DEFAULT_LOGO_WIDTH_CM = 4.65', 'DEFAULT_LOGO_HEIGHT_CM = 2.82', 'DEFAULT_SIGNATURE_WIDTH_CM = 4.00', 'DEFAULT_SIGNATURE_HEIGHT_CM = 3.60'] as $needle) {
            $this->assertStringContainsString($needle, $layout);
        }
        foreach (['logo_width_cm', 'logo_height_cm', 'signature_width_cm', 'signature_height_cm'] as $key) {
            $this->assertStringContainsString("'{$key}'", $layout);
        }
        $this->assertStringContainsString('centerOffsetPixels', $layout);
        $this->assertStringContainsString('$infoStartIndex = min(4, max(1, $columnCount));', $layout);
    }

    public function test_controller_delegates_header_and_footer_to_document_layout(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));

        $this->assertStringContainsString('PriceListExcelDocumentLayout $layout', $controller);
        $this->assertStringContainsString('$layout->header($sheet,$profile,count($columns))', str_replace(' ', '', $controller));
        $this->assertStringContainsString('$layout->footer($sheet,$profile,count($columns),$headerRow+$items->count())', str_replace(' ', '', $controller));
    }
}
