<?php

namespace Modules\Invoices\Services;

use Smalot\PdfParser\Parser;

class ScanInvoiceService
{
    protected $parser;

    public function __construct()
    {
        $this->parser = new Parser;
    }

    /**
     * Quét & trích xuất dữ liệu hóa đơn từ PDF
     */
    public function scan(string $pdfPath): array
    {
        if (! file_exists($pdfPath)) {
            throw new \Exception('File không tồn tại: '.$pdfPath);
        }

        $pdf = $this->parser->parseFile($pdfPath);
        $text = $pdf->getText();

        $lines = array_filter(array_map('trim', explode("\n", $text)));

        return [
            'seller' => $this->extractSeller($lines),
            'buyer' => $this->extractBuyer($lines),
            'invoice' => $this->extractInvoiceInfo($lines),
            'items' => $this->extractItems($lines),
            'summary' => $this->extractSummary($lines),
            'raw' => $text,
        ];
    }

    private function extractSeller($lines)
    {
        $seller = [];

        foreach ($lines as $line) {
            if (str_contains($line, 'CÔNG TY') && ! isset($seller['name'])) {
                $seller['name'] = $line;
            }
            if (str_contains($line, 'Mã số thuế')) {
                preg_match('/(\d{10})/', $line, $m);
                $seller['taxcode'] = $m[1] ?? null;
            }
            if (str_contains($line, 'Địa chỉ')) {
                $seller['address'] = $line;
            }
        }

        return $seller;
    }

    private function extractBuyer($lines)
    {
        $buyer = [];
        $capture = false;

        foreach ($lines as $line) {
            if (str_contains($line, 'Họ tên người mua hàng')) {
                $capture = true;

                continue;
            }

            if ($capture && str_contains($line, 'Mã số thuế')) {
                preg_match('/(\d{10})/', $line, $m);
                $buyer['taxcode'] = $m[1] ?? null;
            }

            if ($capture && str_contains($line, 'Tên đơn vị')) {
                $buyer['name'] = trim(str_replace('Tên đơn vị:', '', $line));
            }

            if ($capture && str_contains($line, 'Địa chỉ')) {
                $buyer['address'] = trim(str_replace('Địa chỉ:', '', $line));
                break;
            }
        }

        return $buyer;
    }

    private function extractInvoiceInfo($lines)
    {
        $info = [];

        // Ghép toàn bộ text lại
        $text = implode("\n", $lines);

        // Regex CỰC KỲ LINH HOẠT cho PDF MISA bị tách dòng
        $pattern = '/Ngày[\s\n]*?(\d{1,2})[\s\n]*?tháng[\s\n]*?(\d{1,2})[\s\n]*?năm[\s\n]*?(\d{4})/ui';

        if (preg_match($pattern, $text, $m)) {
            $info['date'] = sprintf('%s-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // Symbol
        foreach ($lines as $line) {
            if (str_contains($line, 'Ký hiệu')) {
                $info['symbol'] = trim(str_replace('Ký hiệu:', '', $line));
            }

            if (str_contains($line, 'Số:')) {
                // tránh match "Số lượng"
                if (preg_match('/Số:\s*(\d+)/', $line, $mm)) {
                    $info['number'] = $mm[1];
                }
            }
        }

        return $info;
    }

    private function extractItems($lines)
    {
        $items = [];
        $capture = false;
        $current = [];

        foreach ($lines as $line) {

            // bắt đầu bảng
            if (str_contains($line, 'STT') && str_contains($line, 'Tên hàng hóa')) {
                $capture = true;

                continue;
            }

            if (! $capture) {
                continue;
            }

            // detect STT
            if (preg_match('/^\d+$/', trim($line))) {
                if (! empty($current)) {
                    $items[] = $current;
                    $current = [];
                }
                $current['stt'] = trim($line);

                continue;
            }

            // detect tên sản phẩm (dòng thứ 2)
            if (! isset($current['name'])) {
                $current['name'] = $line;

                continue;
            }

            // detect dòng thông tin chính của item
            $pattern = '/^(\S+)\s+(\d{2}\/\d{2}\/\d{4})\s*(.*?)\s+(\d+)\s+([\d\.,]+)\s+([\d\.,]+)$/';

            if (preg_match($pattern, $line, $m)) {

                $current['lot'] = $m[1];
                $current['exp'] = $m[2];
                $current['unit'] = trim($m[3]);
                $current['quantity'] = (int) $m[4];
                $current['price'] = $this->toNumber($m[5]);
                $current['total'] = $this->toNumber($m[6]);
            }

            // kết thúc bảng
            if (str_contains($line, 'Cộng tiền hàng')) {
                if (! empty($current)) {
                    $items[] = $current;
                }
                break;
            }
        }

        return $items;
    }

    private function extractSummary($lines)
    {
        $sum = [];

        foreach ($lines as $line) {
            if (str_contains($line, 'Cộng tiền hàng')) {
                preg_match('/(\d[\d\.]*)$/', $line, $m);
                $sum['subtotal'] = $this->toNumber($m[1] ?? 0);
            }

            if (str_contains($line, 'Thuế suất')) {
                preg_match('/(\d+)%/', $line, $m);
                $sum['vat_rate'] = $m[1] ?? 0;
            }

            if (str_contains($line, 'Tiền thuế')) {
                preg_match('/(\d[\d\.]*)$/', $line, $m);
                $sum['vat_amount'] = $this->toNumber($m[1] ?? 0);
            }

            if (str_contains($line, 'Tổng tiền thanh toán')) {
                preg_match('/(\d[\d\.]*)$/', $line, $m);
                $sum['total'] = $this->toNumber($m[1] ?? 0);
            }
        }

        return $sum;
    }

    private function toNumber($str): int
    {
        return (int) str_replace(['.', ','], ['', ''], $str);
    }
}
