<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\PriceListService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PriceListServiceTest extends TestCase
{
    public function test_it_detects_real_header_and_product_boundaries(): void
    {
        $analysis = app(PriceListService::class)->analyze('TỔNG HỢP');

        $this->assertSame(9, $analysis->headerRow);
        $this->assertSame('X', $analysis->lastHeaderColumn);
        $this->assertCount(24, $analysis->columns);
        $this->assertCount(44, $analysis->products);
        $this->assertSame(10, $analysis->products[0]['row']);
        $this->assertSame(53, $analysis->products[43]['row']);
    }

    public function test_it_filters_products_by_stt_and_name(): void
    {
        $service = app(PriceListService::class);
        $analysis = $service->analyze('TỔNG HỢP');

        $this->assertSame([4], array_column($service->filteredProducts($analysis, 'Trosicam'), 'stt'));
        $this->assertSame([13], array_column($service->filteredProducts($analysis, '13'), 'stt'));
    }

    public function test_it_builds_non_contiguous_columns_and_repositions_signature(): void
    {
        $path = sys_get_temp_dir().'/price-list-'.uniqid().'.xlsx';
        $service = app(PriceListService::class);
        $analysis = $service->analyze('TỔNG HỢP');

        $service->generate([
            'sheet_name' => 'TỔNG HỢP',
            'columns' => 'A,B,E:V',
            'product_rows' => [10, 11, 12],
            'recipient' => 'BỆNH VIỆN KIỂM THỬ',
            'signature_date' => 'Tp.HCM, ngày 01 tháng 01 năm 2026',
            'signature_title' => 'GIÁM ĐỐC CÔNG TY',
            'output_path' => $path,
        ]);

        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('A1:T16', $sheet->getPageSetup()->getPrintArea());
        $this->assertSame('Kính gửi: BỆNH VIỆN KIỂM THỬ', $sheet->getCell('A7')->getValue());
        $this->assertSame('Nồng độ - Hàm lượng', preg_replace('/\s+/u', ' ', $sheet->getCell('C9')->getValue()));
        $this->assertSame('Tp.HCM, ngày 01 tháng 01 năm 2026', $sheet->getCell('K14')->getValue());
        $this->assertSame('dd/mm/yyyy', $sheet->getStyle('T10')->getNumberFormat()->getFormatCode());
        $this->assertCount(1, $sheet->getDrawingCollection());

        @unlink($path);
    }
}
