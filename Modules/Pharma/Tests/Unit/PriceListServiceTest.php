<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\PriceListService;
use Modules\Pharma\Services\Spreadsheet\PriceListWorkbookBuilder;
use Modules\Pharma\Services\Spreadsheet\WorkbookAnalyzer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Tests\TestCase;

class PriceListServiceTest extends TestCase
{
    private string $fixturePath;

    private string $fixtureImagePath;

    private string $exportDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = sys_get_temp_dir().'/pharma-price-list-source-'.uniqid().'.xlsx';
        $this->fixtureImagePath = sys_get_temp_dir().'/pharma-price-list-logo-'.uniqid().'.png';
        $this->exportDirectory = sys_get_temp_dir().'/pharma-price-list-exports-'.uniqid();

        $this->createWorkbookFixture();
    }

    protected function tearDown(): void
    {
        @unlink($this->fixturePath);
        @unlink($this->fixtureImagePath);

        if (is_dir($this->exportDirectory)) {
            foreach (glob($this->exportDirectory.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->exportDirectory);
        }

        parent::tearDown();
    }

    public function test_it_detects_real_header_and_product_boundaries(): void
    {
        $analysis = $this->service()->analyze('TỔNG HỢP');

        $this->assertSame(9, $analysis->headerRow);
        $this->assertSame('X', $analysis->lastHeaderColumn);
        $this->assertCount(24, $analysis->columns);
        $this->assertCount(44, $analysis->products);
        $this->assertSame(10, $analysis->products[0]['row']);
        $this->assertSame(53, $analysis->products[43]['row']);
    }

    public function test_it_filters_products_by_stt_and_name(): void
    {
        $service = $this->service();
        $analysis = $service->analyze('TỔNG HỢP');

        $this->assertSame([4], array_column($service->filteredProducts($analysis, 'Trosicam'), 'stt'));
        $this->assertSame([13], array_column($service->filteredProducts($analysis, '13'), 'stt'));
    }

    public function test_it_builds_non_contiguous_columns_and_repositions_signature(): void
    {
        $service = $this->service();
        $path = $service->generate([
            'sheet_name' => 'TỔNG HỢP',
            'columns' => 'A,B,E:V',
            'product_rows' => [10, 11, 12],
            'recipient' => 'BỆNH VIỆN KIỂM THỬ',
            'signature_date' => 'Tp.HCM, ngày 01 tháng 01 năm 2026',
            'signature_title' => 'GIÁM ĐỐC CÔNG TY',
        ]);

        $this->assertStringStartsWith($this->exportDirectory.DIRECTORY_SEPARATOR, $path);
        $this->assertFileExists($path);

        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('A1:T16', $sheet->getPageSetup()->getPrintArea());
        $this->assertSame('Kính gửi: BỆNH VIỆN KIỂM THỬ', $sheet->getCell('A7')->getValue());
        $this->assertSame('Nồng độ - Hàm lượng', preg_replace('/\s+/u', ' ', $sheet->getCell('C9')->getValue()));
        $this->assertSame('Tp.HCM, ngày 01 tháng 01 năm 2026', $sheet->getCell('K14')->getValue());
        $this->assertSame('dd/mm/yyyy', $sheet->getStyle('T10')->getNumberFormat()->getFormatCode());
        $this->assertCount(1, $sheet->getDrawingCollection());
    }

    public function test_generate_ignores_arbitrary_output_path_input(): void
    {
        $outsidePath = sys_get_temp_dir().'/must-not-be-used-'.uniqid().'.xlsx';
        $service = $this->service();

        $path = $service->generate([
            'sheet_name' => 'TỔNG HỢP',
            'columns' => 'A:B',
            'product_rows' => [10],
            'recipient' => 'TEST',
            'signature_date' => 'TEST',
            'signature_title' => 'TEST',
            'output_path' => $outsidePath,
        ]);

        $this->assertNotSame($outsidePath, $path);
        $this->assertStringStartsWith($this->exportDirectory.DIRECTORY_SEPARATOR, $path);
        $this->assertFileDoesNotExist($outsidePath);
    }

    private function service(): PriceListService
    {
        return new PriceListService(
            app(WorkbookAnalyzer::class),
            app(PriceListWorkbookBuilder::class),
            $this->fixturePath,
            $this->exportDirectory,
        );
    }

    private function createWorkbookFixture(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('TỔNG HỢP');

        $sheet->setCellValue('A1', 'CÔNG TY KIỂM THỬ');
        $sheet->setCellValue('A6', 'BẢNG GIÁ');
        $sheet->setCellValue('A7', 'Kính gửi: KHÁCH HÀNG');
        $sheet->setCellValue('A8', 'Đơn vị tính: VNĐ');

        $headers = [
            'STT',
            'Tên biệt dược',
            'Tên hoạt chất',
            'Giấy phép lưu hành sản phẩm',
            'Nồng độ - Hàm lượng',
            'Dạng bào chế',
            'Đường dùng',
            'Đơn vị tính',
            'Quy cách đóng gói',
            'Nhà sản xuất',
            'Nước sản xuất',
            'Giá kê khai',
            'Giá bán',
            'VAT',
            'Cột O',
            'Cột P',
            'Cột Q',
            'Cột R',
            'Cột S',
            'Cột T',
            'Cột U',
            'Ngày hiệu lực',
            'Cột W',
            'Cột X',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 9], $header);
        }

        for ($stt = 1; $stt <= 44; $stt++) {
            $row = $stt + 9;
            $name = $stt === 4 ? 'Trosicam 15mg' : "Thuốc kiểm thử {$stt}";

            $sheet->setCellValue("A{$row}", $stt);
            $sheet->setCellValue("B{$row}", $name);
            $sheet->setCellValue("C{$row}", "Hoạt chất {$stt}");
            $sheet->setCellValue("D{$row}", "VN-{$stt}");
            $sheet->setCellValue("E{$row}", "Hàm lượng {$stt}");
            $sheet->setCellValue("V{$row}", Date::PHPToExcel(new \DateTimeImmutable('2026-01-01')));
            $sheet->getStyle("V{$row}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        }

        $this->writeTinyPng($this->fixtureImagePath);

        $drawing = new Drawing;
        $drawing->setPath($this->fixtureImagePath);
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($this->fixturePath);
    }

    private function writeTinyPng(string $path): void
    {
        file_put_contents(
            $path,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true)
        );
    }
}
