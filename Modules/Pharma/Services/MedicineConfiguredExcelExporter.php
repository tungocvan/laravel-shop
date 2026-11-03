<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\Medicine;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
        if ($settings['header_enabled']) {
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
                ->setWidth(max(6, min(60, ($profile['widths'][$key] ?? 130) / 7)));
        }
        $sheet->getStyle("A{$row}:{$last}{$row}")->getFont()->setBold(true)->setSize((int) $settings['header_font_size']);
        $sheet->getStyle("A{$row}:{$last}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($settings['header_fill']);
        $sheet->setAutoFilter("A{$row}:{$last}{$row}");
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
        if ($index > 0) {
            $sheet->getStyle('A'.($headerRow + 1).':'.$last.($headerRow + $index))
                ->getFont()->setSize((int) $settings['body_font_size']);
        }
        $sheet->getPageSetup()->setOrientation($settings['orientation'] === 'portrait' ? 'portrait' : 'landscape');
        $sheet->getPageSetup()->setPaperSize($settings['paper_size']);
        $path = tempnam(sys_get_temp_dir(), 'pharma-configured-excel-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
