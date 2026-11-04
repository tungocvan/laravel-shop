<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class InventoryConfiguredExcelExporter
{
    public function download(iterable $rows, array $columns, array $headers, array $widths, array $alignments, array $settings, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $columns, $headers, $widths, $alignments, $settings) {
            $book = new Spreadsheet();
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Bao cao kho');
            $font = $settings['font_family'] ?? 'Times New Roman';
            $book->getDefaultStyle()->getFont()->setName(in_array($font, ['Times New Roman','Arial','Calibri'], true) ? $font : 'Times New Roman');
            $last = Coordinate::stringFromColumnIndex(count($columns));
            $line = 1;
            $inventoryTotal = 0.0;
            $hasInventoryValue = in_array('inventory_value', $columns, true);
            $header = $settings['report_header'] ?? [];
            if (empty($header['company_name']) && ! empty($settings['company_name'])) $header['company_name'] = $settings['company_name'];
            if (! empty($settings['company_name']) && ! array_key_exists('enabled', $header)) $header['enabled'] = true;
            $logoPath = null;
            try {
                if (! empty($header['enabled'])) {
                    $logo = $header['logo_data'] ?? null;
                    if (! empty($header['show_logo']) && is_string($logo) && preg_match('#^data:image/(png|jpeg);base64,#', $logo)) {
                        $binary = base64_decode(substr($logo, strpos($logo, ',') + 1), true);
                        if ($binary !== false && strlen($binary) <= 262144 && @getimagesizefromstring($binary)) {
                            $logoPath = tempnam(sys_get_temp_dir(), 'inv-logo-');
                            file_put_contents($logoPath, $binary);
                            $drawing = new Drawing();
                            $drawing->setPath($logoPath);
                            $drawing->setCoordinates('A1');
                            $drawing->setWidth(max(40, min(220, (int) ($header['logo_width'] ?? 90))));
                            $drawing->setWorksheet($sheet);
                        }
                    }
                    $companyFields = [
                        'company_name' => '', 'address' => 'Địa chỉ: ', 'office' => 'VPĐD: ',
                        'tax_code' => 'Mã số thuế: ', 'phone' => 'Số điện thoại: ',
                        'email' => 'Email: ',
                    ];
                    foreach ($companyFields as $key => $prefix) {
                        $value = trim((string) ($header[$key] ?? ''));
                        if ($value === '') continue;
                        $sheet->mergeCells("A{$line}:{$last}{$line}");
                        $sheet->setCellValue("A{$line}", $prefix.$value);
                        $sheet->getStyle("A{$line}:{$last}{$line}")->getAlignment()
                            ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
                        if ($key === 'company_name') $sheet->getStyle("A{$line}")->getFont()->setBold(true);
                        $sheet->getRowDimension($line)->setRowHeight(-1);
                        $line++;
                    }
                    if ($logoPath) $line = max($line, 7);
                }
                $title = $settings['title'] ?? 'BÁO CÁO KHO';
                $sheet->mergeCells("A{$line}:{$last}{$line}");
                $sheet->setCellValue("A{$line}", $title);
                $sheet->getStyle("A{$line}")->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle("A{$line}:{$last}{$line}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                $sheet->getRowDimension($line)->setRowHeight(-1);
                $line += 2;
                if (! empty($header['enabled'])) {
                    foreach (['recipient' => 'Kính gửi: ', 'intro' => ''] as $key => $prefix) {
                        $value = trim((string) ($header[$key] ?? ''));
                        if ($value === '') continue;
                        $sheet->mergeCells("A{$line}:{$last}{$line}");
                        $sheet->setCellValue("A{$line}", $prefix.$value);
                        $sheet->getStyle("A{$line}:{$last}{$line}")->getAlignment()
                            ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
                        if ($key === 'recipient') $sheet->getStyle("A{$line}")->getFont()->setBold(true);
                        $sheet->getRowDimension($line)->setRowHeight(-1);
                        $line++;
                    }
                    if (! empty($header['recipient']) || ! empty($header['intro'])) $line++;
                }
                $headingLine = $line;
                foreach ($columns as $i => $key) {
                    $letter = Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue($letter.$line, $headers[$key] ?? $key);
                    $sheet->getColumnDimension($letter)->setWidth(max(40, min(600, (int) ($widths[$key] ?? 130))), 'px');

                }
                $style = $sheet->getStyle("A{$line}:{$last}{$line}");
                $style->getFont()->setBold(true)->setSize((int) ($settings['header_font_size'] ?? 12));
                $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                $sheet->getRowDimension($headingLine)->setRowHeight(34);
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($settings['header_fill'] ?? 'EFF4FA');
                $line++;
                $sequence = 0;
                foreach ($rows as $record) {
                    $sequence++;
                    foreach ($columns as $i => $key) {
                        $value = $key === 'stt' ? $sequence : ($record[$key] ?? null);
                        $cell = Coordinate::stringFromColumnIndex($i + 1).$line;
                        if (in_array($key, ['stt','quantity_on_hand','average_cost_price','inventory_value','period_opening','period_in','period_out','period_closing'], true) && $value !== null && is_numeric($value)) {
                            $sheet->setCellValue($cell, (float) $value);
                            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
                        } else {
                            $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }
                        $sheet->getStyle($cell)->getAlignment()->setHorizontal($alignments[$key] ?? 'left')->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                        if ($key === 'inventory_value' && is_numeric($value)) $inventoryTotal += (float) $value;
                    }
                    $sheet->getRowDimension($line)->setRowHeight(-1);
                    $line++;
                }
                if ($hasInventoryValue) {
                    $valueIndex = array_search('inventory_value', $columns, true) + 1;
                    $valueColumn = Coordinate::stringFromColumnIndex($valueIndex);
                    if ($valueIndex > 1) {
                        $before = Coordinate::stringFromColumnIndex($valueIndex - 1);
                        $sheet->mergeCells("A{$line}:{$before}{$line}");
                    }
                    $sheet->setCellValue("A{$line}", 'TỔNG GIÁ TRỊ TỒN');
                    $sheet->setCellValue("{$valueColumn}{$line}", $inventoryTotal);
                    $sheet->getStyle("{$valueColumn}{$line}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("A{$line}:{$last}{$line}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$line}:{$last}{$line}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                    $sheet->getRowDimension($line)->setRowHeight(-1);
                    $line++;
                }
                if (! empty($settings['body_border']) && $line > $headingLine + 1) {
                    $sheet->getStyle("A{$headingLine}:{$last}".($line - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
                $footer = $settings['report_footer'] ?? [];
                if (! empty($footer['enabled'])) {
                    $line += 2;
                    // Signature block occupies the right half of the report, matching Medicine Master exports.
                    $signatureStart = Coordinate::stringFromColumnIndex(max(1, (int) floor(count($columns) / 2) + 1));
                    foreach (['date_text', 'signer_title', 'signer_name'] as $key) {
                        $value = trim((string) ($footer[$key] ?? ''));
                        if ($value === '') continue;
                        if ($key === 'signer_name') $line += max(1, min(12, (int) ($footer['signature_blank_rows'] ?? 4)));
                        $sheet->mergeCells("{$signatureStart}{$line}:{$last}{$line}");
                        $sheet->setCellValue("{$signatureStart}{$line}", $value);
                        $sheet->getStyle("{$signatureStart}{$line}:{$last}{$line}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                        if ($key !== 'date_text') $sheet->getStyle("{$signatureStart}{$line}")->getFont()->setBold(true);
                        $sheet->getRowDimension($line)->setRowHeight(-1);
                        $line++;
                    }
                }
                $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headingLine, $headingLine);
                $sheet->getPageMargins()->setTop((float) ($settings['margin_top'] ?? 0.8) / 2.54);
                $sheet->getPageMargins()->setBottom((float) ($settings['margin_bottom'] ?? 0.8) / 2.54);
                $sheet->getPageMargins()->setLeft((float) ($settings['margin_left'] ?? 0.8) / 2.54);
                $sheet->getPageMargins()->setRight((float) ($settings['margin_right'] ?? 0.8) / 2.54);
                $sheet->getHeaderFooter()->setOddHeader((string) ($settings['print_header'] ?? ''));
                $sheet->getHeaderFooter()->setOddFooter((string) ($settings['print_footer'] ?? ''));
                $sheet->getPageSetup()->setOrientation(($settings['orientation'] ?? 'landscape') === 'portrait' ? 'portrait' : 'landscape');
                $sheet->getPageSetup()->setPaperSize(match ($settings['paper_size'] ?? 'A4') {'A3' => '8', 'LETTER' => '1', default => '9'});
                $sheet->getStyle('A'.($headingLine + 1).':'.$last.max($headingLine + 1, $line - 1))->getFont()->setSize((int) ($settings['body_font_size'] ?? 11));
                $sheet->freezePane('A'.($headingLine + 1));
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
                if ($logoPath && is_file($logoPath)) @unlink($logoPath);
            }
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
