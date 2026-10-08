<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\MedicineExcelProfileService;
use Modules\Pharma\Services\MedicineExcelRelatedDataService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MedicineExcelController extends Controller
{
    public function export(Request $request, MedicineExcelProfileService $profiles, MedicineExcelRelatedDataService $related): BinaryFileResponse
    {
        $validated = $request->validate([
            'profile_id' => ['nullable', 'integer'],
            'ids' => ['nullable', 'string', 'max:5000'],
        ]);
        $profile = $profiles->forUser((int) auth('admin')->id(), isset($validated['profile_id']) ? (int) $validated['profile_id'] : null);
        $columns = array_values(array_filter($profile['columns'], fn ($key) => isset(MedicineExcelProfileService::COLUMNS[$key])));
        abort_if($columns === [], 422, 'Cấu hình Excel không có cột hợp lệ.');
        $ids = collect(explode(',', (string) ($validated['ids'] ?? '')))
            ->filter(fn ($id) => ctype_digit(trim($id)) && (int) $id > 0)
            ->map(fn ($id) => (int) trim($id))->unique()->take(500)->all();

        $query = Medicine::query()->with('variants:id,medicine_id,sku,declared_price,is_default');
        if ($ids !== []) {
            $query->whereKey($ids);
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh muc thuoc');
        $last = Coordinate::stringFromColumnIndex(count($columns));
        $row = 1;
        $settings = array_replace($profiles->defaults()['settings'], $profile['settings'] ?? []);
        $sheet->getParent()->getDefaultStyle()->getFont()->setName($settings['font_family']);
        if ($settings['header_enabled'] ?? true) {
            foreach (['company_name', 'title'] as $key) {
                $sheet->mergeCells("A{$row}:{$last}{$row}");
                $sheet->setCellValue("A{$row}", $settings[$key] ?? '');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize($key === 'title' ? 16 : 12);
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
            }
            $row++;
        }
        $headerRow = $row;
        foreach ($columns as $index => $key) {
            $cell = Coordinate::stringFromColumnIndex($index + 1).$headerRow;
            $sheet->setCellValue($cell, $profile['headers'][$key] ?? MedicineExcelProfileService::COLUMNS[$key]);
        }
        $headerStyle = $sheet->getStyle("A{$headerRow}:{$last}{$headerRow}");
        $headerStyle->getFont()->setBold(true)->setName($settings['font_family'])->setSize((int) $settings['header_font_size']);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($settings['header_fill']);
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('DCE4ED');
        $sheet->getStyle("A{$headerRow}:{$last}{$headerRow}")->getAlignment()->setWrapText(true);
        $sheet->setAutoFilter("A{$headerRow}:{$last}{$headerRow}");
        $sheet->freezePane('A'.($headerRow + 1));
        $relatedKeys = array_keys($related->emptyValues());
        $needsRelated = count(array_intersect($columns, $relatedKeys)) > 0;
        $index = 0;
        foreach ($query->orderBy('id')->lazy(200) as $medicine) {
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
            ];
            if ($needsRelated) {
                $values = array_merge($values, $related->preview($medicine));
            }
            $dataRow = $headerRow + $index;
            foreach ($columns as $col => $key) {
                $cell = Coordinate::stringFromColumnIndex($col + 1).$dataRow;
                $value = $values[$key] ?? null;
                if (in_array($key, ['stt', 'declared_price', 'cost_price', 'winning_price', 'allocated_quantity'], true) && is_numeric($value)) {
                    $sheet->setCellValue($cell, (float) $value);
                    if (in_array($key, ['declared_price', 'cost_price', 'winning_price', 'allocated_quantity'], true)) {
                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
                    }
                } else {
                    $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                }
            }
        }
        if ($index > 0) {
            $bodyStyle = $sheet->getStyle('A'.($headerRow + 1).':'.$last.($headerRow + $index));
            $bodyStyle->getFont()->setName($settings['font_family'])->setSize((int) $settings['body_font_size']);
            if ($settings['body_border']) {
                $bodyStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('DCE4ED');
            }
        }
        foreach ($columns as $col => $key) {
            $letter = Coordinate::stringFromColumnIndex($col + 1);
            $sheet->getColumnDimension($letter)->setWidth(max(6, min(60, (float) ($profile['widths'][$key] ?? 130) / 7)));
            $sheet->getStyle($letter.$headerRow.':'.$letter.($headerRow + $index))
                ->getAlignment()->setHorizontal($profile['alignments'][$key] ?? 'left')
                ->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        }
        $page = $sheet->getPageSetup();
        $page->setPaperSize(match ($settings['paper_size'] ?? 'A4') {
            'A3' => PageSetup::PAPERSIZE_A3,
            'LETTER' => PageSetup::PAPERSIZE_LETTER,
            default => PageSetup::PAPERSIZE_A4,
        });
        $page->setOrientation(($settings['orientation'] ?? 'landscape') === 'portrait'
            ? PageSetup::ORIENTATION_PORTRAIT : PageSetup::ORIENTATION_LANDSCAPE);
        $page->setFitToWidth(1);
        $page->setFitToHeight(0);
        $path = tempnam(sys_get_temp_dir(), 'medicine-excel-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, 'danh-muc-thuoc-theo-cau-hinh-'.now()->format('Ymd-His').'.xlsx')
            ->deleteFileAfterSend(true);
    }
}
