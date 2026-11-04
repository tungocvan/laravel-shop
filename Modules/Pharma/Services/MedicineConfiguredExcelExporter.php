<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\Medicine;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Shared\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing as WorksheetDrawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class MedicineConfiguredExcelExporter
{
    public function export(array $ids, array $profile, bool $canSupplierPricing): string
    {
        $allowed = MedicineExcelProfileService::COLUMNS;
        if (! $canSupplierPricing) {
            unset($allowed['cost_price'], $allowed['supplier_name']);
        }
        $columns = array_values(array_filter($profile['columns'] ?? [], fn ($key) => isset($allowed[$key])));
        abort_if($columns === [], 422, 'Mẫu Excel không có cột được phép xuất.');

        $settings = array_replace(app(MedicineExcelProfileService::class)->defaults()['settings'], $profile['settings'] ?? []);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh muc thuoc');
        $last = Coordinate::stringFromColumnIndex(count($columns));
        $row = 1;
        $spreadsheet->getDefaultStyle()->getFont()->setName($settings['font_family']);
        $logoPath = null;
        $reportHeader = $settings['report_header'] ?? [];
        if ($reportHeader['enabled'] ?? false) {
            $logo = $reportHeader['logo_data'] ?? null;
            $hasLogo = ($reportHeader['show_logo'] ?? false) && is_string($logo) && preg_match('#^data:image/(png|jpeg);base64,#', $logo);
            $textStart = $hasLogo && count($columns) >= 4 ? Coordinate::stringFromColumnIndex(3) : 'A';
            if ($hasLogo && count($columns) >= 4) {
                $binary = base64_decode(substr($logo, strpos($logo, ',') + 1), true);
                if ($binary !== false && strlen($binary) <= 262144 && @getimagesizefromstring($binary)) {
                    $logoPath = tempnam(sys_get_temp_dir(), 'pharma-logo-');
                    file_put_contents($logoPath, $binary);
                    $drawing = new WorksheetDrawing();
                    $drawing->setPath($logoPath);
                    $drawing->setCoordinates('A1');
                    $drawing->setWidth((int) ($reportHeader['logo_width'] ?? 110));
                    $drawing->setWorksheet($sheet);
                }
            }
            $lines = array_filter([
                $reportHeader['company_name'] ?: $settings['company_name'],
                $reportHeader['address'] ?? '',
                $reportHeader['office'] ?? '',
                $reportHeader['tax_code'] ?? '',
                $reportHeader['phone'] ?? '',
                $reportHeader['email'] ?? '',
            ], fn ($value) => trim((string) $value) !== '');
            $headerStart = $row;
            foreach ($lines as $line) {
                $sheet->mergeCells("{$textStart}{$row}:{$last}{$row}");
                $sheet->setCellValueExplicit("{$textStart}{$row}", (string) $line, DataType::TYPE_STRING);
                $sheet->getStyle("{$textStart}{$row}")->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;
            }
            if ($logoPath !== null) {
                $minimumRows = (int) ceil(($settings['report_header']['logo_width'] ?? 110) / 20);
                $row = max($row, $headerStart + $minimumRows);
            }
            $row++;
            $sheet->mergeCells("A{$row}:{$last}{$row}");
            $sheet->setCellValue("A{$row}", $settings['title']);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize((int) $settings['header_font_size'] + 2);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
            foreach (['recipient', 'intro'] as $field) {
                if (trim((string) ($reportHeader[$field] ?? '')) === '') continue;
                $sheet->mergeCells("A{$row}:{$last}{$row}");
                $sheet->setCellValueExplicit("A{$row}", (string) $reportHeader[$field], DataType::TYPE_STRING);
                $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($row)->setRowHeight($field === 'intro' ? 44 : 22);
                $row++;
            }
            $row++;
        } elseif ($settings['header_enabled']) {
            foreach (['company_name', 'title'] as $key) {
                $sheet->mergeCells("A{$row}:{$last}{$row}");
                $sheet->setCellValue("A{$row}", $settings[$key]);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
            }
            $row++;
        }
        $headerRow = $row;
        foreach ($columns as $i => $key) {
            $cell = Coordinate::stringFromColumnIndex($i + 1).$row;
            $sheet->setCellValue($cell, $profile['headers'][$key] ?? $allowed[$key]);
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))
                ->setWidth(Drawing::pixelsToCellDimension((int) ($profile['widths'][$key] ?? 130), $spreadsheet->getDefaultStyle()->getFont()));
        }
        $sheet->getStyle("A{$row}:{$last}{$row}")->getFont()->setBold(true)->setSize((int) $settings['header_font_size']);
        $sheet->getStyle("A{$row}:{$last}{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(-1);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
        $sheet->getStyle("A{$row}:{$last}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($settings['header_fill']);
        $sheet->freezePane('A'.($row + 1));

        $related = app(MedicineExcelRelatedDataService::class);
        $index = 0;
        foreach (Medicine::query()->whereKey($ids)->with('variants')->orderBy('id')->lazy(200) as $medicine) {
            $index++;
            $variant = $medicine->variants->firstWhere('is_default', true) ?? $medicine->variants->first();
            $values = [
                'stt' => $index,
                'medicine_code' => $medicine->medicine_code,
                'name' => $medicine->name,
                'product_type' => Medicine::productTypeOptions()[$medicine->product_type] ?? 'Tân dược',
                'sku' => $variant?->sku,
                'registration_number' => $medicine->registration_number_primary ?: $medicine->registration_number,
                'circular_group' => $medicine->circular_group,
                'circular_order_number' => $medicine->circular_order_number,
                'visa_validity_date' => $medicine->visa_validity_date?->format('d/m/Y') ?? '',
                'gmp_certification_date' => $medicine->gmp_certification_date?->format('d/m/Y') ?? '',
                'is_special_control' => $medicine->getRawOriginal('is_special_control') === null ? '' : ($medicine->is_special_control ? 'Có' : 'Không'),
                'active_ingredients' => $medicine->active_ingredients,
                'concentration' => $medicine->concentration,
                'dosage_form' => $medicine->dosage_form,
                'route_of_administration' => $medicine->route_of_administration,
                'unit' => $medicine->unit,
                'packaging_specification' => $medicine->packaging_specification,
                'declared_price' => $variant?->declared_price ?? $medicine->declared_price,
                'registered_company' => $medicine->registered_company,
                'manufacturing_company' => $medicine->manufacturing_company,
                'manufacturing_country' => $medicine->manufacturing_country,
                'profile_status' => $medicine->profile_status,
                'shelf_life' => $medicine->shelf_life,
            ] + $related->preview($medicine);
            $dataRow = $headerRow + $index;
            foreach ($columns as $i => $key) {
                $cell = Coordinate::stringFromColumnIndex($i + 1).$dataRow;
                $value = $values[$key] ?? '';
                if (in_array($key, ['stt', 'declared_price', 'cost_price', 'winning_price', 'allocated_quantity'], true) && is_numeric($value)) {
                    $sheet->setCellValue($cell, (float) $value);
                    if ($key !== 'stt') $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
                } else {
                    $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                }
                $sheet->getStyle($cell)->getAlignment()->setHorizontal($profile['alignments'][$key] ?? 'left');
            }
        }
        $borderStyle = ($settings['body_border'] ?? true) ? Border::BORDER_HAIR : Border::BORDER_THIN;
        $borderColor = ($settings['body_border'] ?? true) ? 'D8DEE8' : '333333';
        $sheet->getStyle("A{$headerRow}:{$last}{$headerRow}")->getBorders()->getAllBorders()
            ->setBorderStyle($borderStyle)->getColor()->setRGB($borderColor);
        if ($index > 0) {
            $bodyRange = 'A'.($headerRow + 1).':'.$last.($headerRow + $index);
            $sheet->getStyle($bodyRange)->getFont()->setSize((int) $settings['body_font_size']);
            $sheet->getStyle($bodyRange)->getAlignment()
                ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($bodyRange)->getBorders()->getAllBorders()
                ->setBorderStyle($borderStyle)->getColor()->setRGB($borderColor);
            for ($dataRow = $headerRow + 1; $dataRow <= $headerRow + $index; $dataRow++) {
                $sheet->getRowDimension($dataRow)->setRowHeight(-1);
            }
        }
        $footer = $settings['report_footer'] ?? [];
        if ($footer['enabled'] ?? false) {
            $footerRow = $headerRow + $index + 2;
            foreach (['date_text', 'signer_title'] as $field) {
                if (trim((string) ($footer[$field] ?? '')) === '') continue;
                $sheet->mergeCells("A{$footerRow}:{$last}{$footerRow}");
                $sheet->setCellValueExplicit("A{$footerRow}", (string) $footer[$field], DataType::TYPE_STRING);
                $sheet->getStyle("A{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                if ($field === 'signer_title') $sheet->getStyle("A{$footerRow}")->getFont()->setBold(true);
                $footerRow++;
            }
            $footerRow += (int) ($footer['signature_rows'] ?? 4);
            if (trim((string) ($footer['signer_name'] ?? '')) !== '') {
                $sheet->mergeCells("A{$footerRow}:{$last}{$footerRow}");
                $sheet->setCellValueExplicit("A{$footerRow}", (string) $footer['signer_name'], DataType::TYPE_STRING);
                $sheet->getStyle("A{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("A{$footerRow}")->getFont()->setBold(true);
            }
        }
        $sheet->getPageSetup()->setOrientation($settings['orientation'] === 'portrait' ? 'portrait' : 'landscape');
        $sheet->getPageSetup()->setPaperSize($settings['paper_size']);
        $sheet->getPageSetup()->setFitToPage((bool) ($settings['fit_to_page'] ?? false));
        if ($settings['fit_to_page'] ?? false) {
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(1);
        }
        $margins = $sheet->getPageMargins();
        foreach (['top', 'bottom', 'left', 'right', 'header', 'footer'] as $side) {
            $setter = 'set'.ucfirst($side);
            $margins->{$setter}(max(0, min(5, (float) ($settings['margin_'.$side.'_cm'] ?? 0.8))) / 2.54);
        }
        $sheet->getPageSetup()->setHorizontalCentered((bool) ($settings['center_horizontally'] ?? true));
        $sheet->getPageSetup()->setVerticalCentered((bool) ($settings['center_vertically'] ?? false));
        $path = tempnam(sys_get_temp_dir(), 'pharma-configured-excel-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        if ($logoPath !== null) @unlink($logoPath);

        return $path;
    }
}
