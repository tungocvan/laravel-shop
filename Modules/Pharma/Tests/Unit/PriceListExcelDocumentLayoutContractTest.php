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

        $this->assertSame('CÔNG TY TEST', $sheet->getCell('C1')->getValue());
        $this->assertSame('Địa chỉ: 123 Đường Test', $sheet->getCell('C2')->getValue());
        $this->assertSame('Mã số thuế: 0312345678', $sheet->getCell('C3')->getValue());
        $this->assertSame('BẢNG CHÀO GIÁ', $sheet->getCell('A6')->getValue());
        $this->assertGreaterThan(6, $nextRow);

        $layout->footer($sheet, $profile, 14, 20);
        $merged = $sheet->getMergeCells();

        // 14 columns => last five are J:N. Every footer line stays inside that signature block.
        $this->assertContains('J22:N22', $merged);
        $this->assertContains('J23:N23', $merged);
        $this->assertContains('J24:N26', $merged);
        $this->assertContains('J27:N27', $merged);
        $this->assertSame('GIÁM ĐỐC CÔNG TY', $sheet->getCell('J23')->getValue());
        $this->assertSame('NGUYỄN VĂN A', $sheet->getCell('J27')->getValue());
    }

    public function test_controller_delegates_header_and_footer_to_document_layout(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));

        $this->assertStringContainsString('PriceListExcelDocumentLayout $layout', $controller);
        $this->assertStringContainsString('$layout->header($sheet,$profile,count($columns))', str_replace(' ', '', $controller));
        $this->assertStringContainsString('$layout->footer($sheet,$profile,count($columns),$headerRow+$items->count())', str_replace(' ', '', $controller));
    }
}
