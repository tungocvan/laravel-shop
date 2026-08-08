<?php

namespace Modules\Invoices\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InvoiceExportService
{
    public function exportItemsToExcel(array $data, string $outputPath)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // ===== HEADER =====
        $headers = [
            'buyer_name',
            'buyer_taxcode',
            'buyer_address',
            'invoice_symbol',
            'invoice_number',
            'invoice_date',

            'stt',
            'name',
            'lot',
            'exp',
            'unit',
            'quantity',
            'price',
            'total',
        ];

        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, 1, $h);
            $col++;
        }

        // ===== BODY =====
        $row = 2;

        foreach ($data['items'] as $item) {
            $col = 1;

            // Lặp thông tin buyer + invoice vào mỗi dòng item
            $fixed = [
                $data['buyer']['name'] ?? '',
                $data['buyer']['taxcode'] ?? '',
                $data['buyer']['address'] ?? '',

                $data['invoice']['symbol'] ?? '',
                $data['invoice']['number'] ?? '',
                $data['invoice']['date'] ?? '',
            ];

            foreach ($fixed as $value) {
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }

            // Các field thuộc item
            $dynamic = [
                $item['stt'] ?? '',
                $item['name'] ?? '',
                $item['lot'] ?? '',
                $item['exp'] ?? '',
                $item['unit'] ?? '',
                $item['quantity'] ?? '',
                $item['price'] ?? '',
                $item['total'] ?? '',
            ];

            foreach ($dynamic as $value) {
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }

            $row++;
        }

        // Xuất file
        $writer = new Xlsx($spreadsheet);
        $writer->save($outputPath);

        return $outputPath;
    }

    public function exportItemsToExcelMerged(array $allItems, string $outputPath)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = [
            'source_file',
            'buyer_name',
            'buyer_taxcode',
            'buyer_address',
            'invoice_symbol',
            'invoice_number',
            'invoice_date',

            'stt',
            'name',
            'lot',
            'exp',
            'unit',
            'quantity',
            'price',
            'total',
        ];

        $col = 1;
        foreach ($headers as $title) {
            $sheet->setCellValueByColumnAndRow($col, 1, $title);
            $col++;
        }

        // Body
        $row = 2;

        foreach ($allItems as $item) {
            $col = 1;

            $fixed = [
                $item['source_file'] ?? '',
                $item['buyer']['name'] ?? '',
                $item['buyer']['taxcode'] ?? '',
                $item['buyer']['address'] ?? '',
                $item['invoice']['symbol'] ?? '',
                $item['invoice']['number'] ?? '',
                $item['invoice']['date'] ?? '',
            ];

            foreach ($fixed as $v) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $v);
            }

            // Item fields
            $dynamic = [
                $item['stt'] ?? '',
                $item['name'] ?? '',
                $item['lot'] ?? '',
                $item['exp'] ?? '',
                $item['unit'] ?? '',
                $item['quantity'] ?? '',
                $item['price'] ?? '',
                $item['total'] ?? '',
            ];

            foreach ($dynamic as $v) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $v);
            }

            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($outputPath);

        return $outputPath;
    }
}
