<?php

namespace Modules\Pharma\Console\Commands;

use Illuminate\Console\Command;
use Modules\Pharma\Services\PriceListService;
use Throwable;

class GeneratePriceListCommand extends Command
{
    protected $signature = 'pharma:price-list
        {--sheet=TỔNG HỢP : Tên sheet nguồn}
        {--columns=A:X : Danh sách hoặc khoảng cột, ví dụ A,B,E:V}
        {--products=* : STT sản phẩm; bỏ trống để lấy tất cả}
        {--recipient=QUÝ KHÁCH HÀNG : Tên đơn vị nhận báo giá}
        {--signature-date= : Dòng ngày tháng ở phần chữ ký}
        {--signature-title=GIÁM ĐỐC CÔNG TY : Chức danh người ký}
        {--output= : Đường dẫn XLSX đầu ra trong máy chủ}';

    protected $description = 'Tạo bảng giá từ workbook tổng hợp của module Pharma';

    public function handle(PriceListService $service): int
    {
        try {
            $analysis = $service->analyze((string) $this->option('sheet'));
            $requestedStt = $this->parseProductStt((array) $this->option('products'));
            $rows = array_column(
                array_filter(
                    $analysis->products,
                    static fn (array $product): bool => $requestedStt === [] || in_array($product['stt'], $requestedStt, true)
                ),
                'row'
            );

            if ($requestedStt !== []) {
                $foundStt = array_column(array_filter(
                    $analysis->products,
                    static fn (array $product): bool => in_array($product['stt'], $requestedStt, true)
                ), 'stt');
                $missing = array_values(array_diff($requestedStt, $foundStt));
                if ($missing !== []) {
                    $this->error('Không tìm thấy STT: '.implode(', ', $missing));

                    return self::FAILURE;
                }
            }

            $output = $service->generate([
                'sheet_name' => $analysis->sheetName,
                'columns' => (string) $this->option('columns'),
                'product_rows' => $rows,
                'recipient' => (string) $this->option('recipient'),
                'signature_date' => $this->option('signature-date') ?: null,
                'signature_title' => (string) $this->option('signature-title'),
                'output_path' => $this->option('output') ?: null,
            ]);

            $this->info('Đã tạo bảng giá: '.$output);
            $this->line('Sản phẩm: '.count($rows).' | Cột: '.$this->option('columns'));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function parseProductStt(array $values): array
    {
        $result = [];

        foreach ($values as $value) {
            foreach (explode(',', (string) $value) as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                if (preg_match('/^(\d+)-(\d+)$/', $part, $matches)) {
                    if ((int) $matches[1] > (int) $matches[2]) {
                        throw new \InvalidArgumentException("Khoảng STT không hợp lệ: {$part}");
                    }
                    $result = [...$result, ...range((int) $matches[1], (int) $matches[2])];
                } elseif (ctype_digit($part)) {
                    $result[] = (int) $part;
                } else {
                    throw new \InvalidArgumentException("STT không hợp lệ: {$part}");
                }
            }
        }

        return array_values(array_unique($result));
    }
}
