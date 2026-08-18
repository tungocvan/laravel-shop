<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Services\SyncedPricingExportPreferenceService;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SyncedPricingExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $validated = $request->validate(['selected_ids' => ['required', 'array', 'min:1', 'max:5000'], 'selected_ids.*' => ['required', 'integer', 'min:1'], 'export_profile_id' => ['nullable', 'integer', 'min:1']]);
        $ids = array_values(array_unique(array_map('intval', $validated['selected_ids'])));
        $profileId = isset($validated['export_profile_id']) ? (int) $validated['export_profile_id'] : null;
        $preference = app(SyncedPricingExportPreferenceService::class)->forUser((int) Auth::guard('admin')->id(), $profileId);
        $selectedLookup = array_fill_keys($preference['selected_columns'], true);
        $requestedColumns = array_values(array_filter($preference['column_order'], fn (string $key): bool => isset($selectedLookup[$key], SyncedPricingExportPreferenceService::COLUMNS[$key])));
        abort_if($requestedColumns === [], 422, 'Cấu hình xuất chưa chọn cột nào.');
        $items = PricingResult::query()->whereIn('id', $ids)->orderBy('id')->get();
        abort_if($items->isEmpty(), 422, 'Không tìm thấy dữ liệu đồng bộ đã chọn để xuất Excel.');

        $temporaryPath = tempnam(sys_get_temp_dir(), 'msc-synced-pricing-');
        abort_if($temporaryPath === false, 500, 'Không thể tạo file Excel tạm.');
        $excelPath = $temporaryPath.'.xlsx';
        @unlink($temporaryPath);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dữ liệu đồng bộ');
        $headerFooter = (array) ($preference['header_footer'] ?? []);
        $withHeaderFooter = (bool) ($headerFooter['enabled'] ?? false);
        $tableHeaderRow = $withHeaderFooter ? 9 : 1;
        $dataStartRow = $tableHeaderRow + 1;
        $displayColumnCount = max(3, count($requestedColumns));
        $lastDisplayColumn = $sheet->getCell([$displayColumnCount, 1])->getColumn();

        foreach ($requestedColumns as $columnIndex => $key) {
            $cell = $sheet->getCell([$columnIndex + 1, $tableHeaderRow]);
            $cell->setValue($preference['headers'][$key] ?? SyncedPricingExportPreferenceService::COLUMNS[$key]['label']);
            $widthPixels = $preference['widths'][$key] ?? SyncedPricingExportPreferenceService::COLUMNS[$key]['width'];
            $sheet->getColumnDimension($cell->getColumn())->setAutoSize(false)->setWidth((float) $widthPixels, 'px');
        }
        for ($columnIndex = count($requestedColumns) + 1; $columnIndex <= $displayColumnCount; $columnIndex++) {
            $sheet->getColumnDimension($sheet->getCell([$columnIndex, 1])->getColumn())->setAutoSize(false)->setWidth(120, 'px');
        }
        if ($withHeaderFooter) {
            $this->renderHeader($sheet, $preference, $headerFooter, $requestedColumns, $lastDisplayColumn);
        }

        $lastHeaderCell = $sheet->getCell([count($requestedColumns), $tableHeaderRow])->getCoordinate();
        $sheet->getStyle("A{$tableHeaderRow}:{$lastHeaderCell}")->getFont()->setBold(true);
        $sheet->getStyle("A{$tableHeaderRow}:{$lastHeaderCell}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($tableHeaderRow)->setRowHeight(-1);

        foreach ($items->values() as $rowIndex => $item) {
            $excelRow = $dataStartRow + $rowIndex;
            foreach ($requestedColumns as $columnIndex => $key) {
                $cell = $sheet->getCell([$columnIndex + 1, $excelRow]);
                $configuredType = $preference['data_types'][$key] ?? SyncedPricingExportPreferenceService::COLUMNS[$key]['type'];
                $this->writeTypedValue($cell, $this->value($item, $key, $rowIndex + 1), $key === 'gdklh_gpnk' ? 'string' : $configuredType, (int) ($preference['decimals'][$key] ?? 0));
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(-1);
        }

        $dataEndRow = $dataStartRow + $items->count() - 1;
        $lastTableColumn = $sheet->getCell([count($requestedColumns), $tableHeaderRow])->getColumn();
        $sheet->getStyle("A{$tableHeaderRow}:{$lastTableColumn}{$dataEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$tableHeaderRow}:{$lastTableColumn}{$dataEndRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        foreach ($requestedColumns as $columnIndex => $key) {
            $columnLetter = $sheet->getCell([$columnIndex + 1, $tableHeaderRow])->getColumn();
            $horizontal = match ($preference['alignments'][$key] ?? SyncedPricingExportPreferenceService::COLUMNS[$key]['align']) { 'center' => Alignment::HORIZONTAL_CENTER, 'right' => Alignment::HORIZONTAL_RIGHT, default => Alignment::HORIZONTAL_LEFT };
            $sheet->getStyle("{$columnLetter}{$dataStartRow}:{$columnLetter}{$dataEndRow}")->getAlignment()->setHorizontal($horizontal)->setWrapText(true);
        }
        if ($withHeaderFooter) {
            $this->renderFooter($sheet, $preference, $headerFooter, $requestedColumns, $displayColumnCount, $dataEndRow);
        }
        $sheet->freezePane('A'.$dataStartRow);
        (new Xlsx($spreadsheet))->save($excelPath);
        $spreadsheet->disconnectWorksheets();

        return response()->download($excelPath, 'Muasamcong-Danh-sach-da-dong-bo-'.now()->format('Ymd-His').'.xlsx', ['Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff'])->deleteFileAfterSend(true);
    }

    private function renderHeader($sheet, array $preference, array $settings, array $requestedColumns, string $lastDisplayColumn): void
    {
        $sheet->mergeCells('A1:B5');
        foreach ([1, 2, 3, 4, 5] as $row) {
            if ($lastDisplayColumn !== 'C') {
                $sheet->mergeCells("C{$row}:{$lastDisplayColumn}{$row}");
            }
        }
        $sheet->setCellValue('C1', (string) ($settings['company_name'] ?? ''));
        $sheet->setCellValue('C2', 'Địa chỉ: '.(string) ($settings['address'] ?? ''));
        $sheet->setCellValue('C3', 'Mã số thuế: '.(string) ($settings['tax_code'] ?? ''));
        $sheet->setCellValue('C4', 'Số điện thoại: '.(string) ($settings['phone'] ?? ''));
        $sheet->setCellValue('C5', 'Email: '.(string) ($settings['email'] ?? ''));
        $sheet->getStyle("C1:{$lastDisplayColumn}5")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A6:{$lastDisplayColumn}6");
        $sheet->setCellValue('A6', (string) ($settings['title'] ?? 'BẢNG BÁO GIÁ'));
        $sheet->getStyle("A6:{$lastDisplayColumn}6")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("A6:{$lastDisplayColumn}6")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->mergeCells("A7:{$lastDisplayColumn}7");
        $sheet->setCellValue('A7', 'Kính gửi: '.(string) ($settings['recipient'] ?? ''));
        $sheet->getStyle("A7:{$lastDisplayColumn}7")->getFont()->setBold(true);
        $sheet->getStyle("A7:{$lastDisplayColumn}7")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->mergeCells("A8:{$lastDisplayColumn}8");
        $sheet->setCellValue('A8', (string) ($settings['intro'] ?? ''));
        $sheet->getStyle("A8:{$lastDisplayColumn}8")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension(8)->setRowHeight(-1);
        $logoPath = $this->assetPath($preference['logo_path'] ?? null);
        if ($logoPath !== null) {
            $drawing = new Drawing;
            $drawing->setName('Logo công ty')->setPath($logoPath)->setResizeProportional(true)->setHeight(86)->setCoordinates('A1');
            $drawing->setOffsetX((int) max(0, ($this->columnRegionWidth($preference, $requestedColumns, 1, 2) - $drawing->getWidth()) / 2))->setOffsetY(5)->setWorksheet($sheet);
        }
    }

    private function renderFooter($sheet, array $preference, array $settings, array $requestedColumns, int $displayColumnCount, int $dataEndRow): void
    {
        $dateRow = $dataEndRow + 2;
        $titleRow = $dateRow + 1;
        $signatureRow = $titleRow + 1;
        $nameRow = $signatureRow + 1;
        $startIndex = max(1, $displayColumnCount - 2);
        $startColumn = $sheet->getCell([$startIndex, 1])->getColumn();
        $endColumn = $sheet->getCell([$displayColumnCount, 1])->getColumn();
        foreach ([$dateRow, $titleRow, $signatureRow, $nameRow] as $row) {
            $sheet->mergeCells("{$startColumn}{$row}:{$endColumn}{$row}");
        }
        $year = trim((string) ($settings['footer_year'] ?? '')) ?: now()->format('Y');
        $location = trim((string) ($settings['footer_location'] ?? 'Tp.HCM'));
        $sheet->setCellValue("{$startColumn}{$dateRow}", "{$location}, ngày…..tháng…...năm {$year}");
        $sheet->setCellValue("{$startColumn}{$titleRow}", (string) ($settings['signatory_title'] ?? 'GIÁM ĐỐC CÔNG TY'));
        $sheet->getStyle("{$startColumn}{$titleRow}:{$endColumn}{$titleRow}")->getFont()->setBold(true);
        $sheet->getRowDimension($signatureRow)->setRowHeight(78);
        $sheet->setCellValue("{$startColumn}{$nameRow}", (string) ($settings['signatory_name'] ?? ''));
        $sheet->getStyle("{$startColumn}{$nameRow}:{$endColumn}{$nameRow}")->getFont()->setBold(true);
        $signaturePath = $this->assetPath($preference['signature_path'] ?? null);
        if ($signaturePath !== null) {
            $drawing = new Drawing;
            $drawing->setName('Chữ ký Giám đốc')->setPath($signaturePath)->setResizeProportional(true)->setHeight(70)->setCoordinates("{$startColumn}{$signatureRow}");
            $drawing->setOffsetX((int) max(0, ($this->columnRegionWidth($preference, $requestedColumns, $startIndex, $displayColumnCount) - $drawing->getWidth()) / 2))->setOffsetY(3)->setWorksheet($sheet);
        }
        $sheet->getStyle("{$startColumn}{$dateRow}:{$endColumn}{$nameRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    }

    private function assetPath(mixed $storedPath): ?string
    {
        $path = is_string($storedPath) ? trim($storedPath) : '';
        if ($path === '') return null;
        try { $absolutePath = Storage::disk('local')->path($path); } catch (\Throwable) { return null; }
        return is_file($absolutePath) && is_readable($absolutePath) ? $absolutePath : null;
    }

    private function columnRegionWidth(array $preference, array $requestedColumns, int $startIndex, int $endIndex): int
    {
        $total = 0;
        for ($index = $startIndex; $index <= $endIndex; $index++) {
            $key = $requestedColumns[$index - 1] ?? null;
            $total += $key !== null ? (int) ($preference['widths'][$key] ?? SyncedPricingExportPreferenceService::COLUMNS[$key]['width'] ?? 120) : 120;
        }
        return $total;
    }

    private function writeTypedValue(Cell $cell, mixed $value, string $type, int $decimals = 0): void
    {
        if ($value === null || $value === '') { $cell->setValue(null); return; }
        if ($type === 'string') { $cell->setValueExplicit((string) $value, DataType::TYPE_STRING); $cell->getStyle()->getNumberFormat()->setFormatCode('@'); return; }
        if ($type === 'number') {
            if (is_numeric($value)) { $cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC); $cell->getStyle()->getNumberFormat()->setFormatCode($this->numberFormat($decimals)); return; }
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING); return;
        }
        if ($type === 'date') {
            $date = $this->parseDate($value);
            if ($date !== null) { $cell->setValue(ExcelDate::PHPToExcel($date)); $cell->getStyle()->getNumberFormat()->setFormatCode('dd/mm/yyyy'); return; }
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING); return;
        }
        $cell->setValue($value);
    }

    private function numberFormat(int $decimals): string
    {
        $decimals = max(0, min(6, $decimals));
        return $decimals === 0 ? '#,##0' : '#,##0.'.str_repeat('0', $decimals);
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) return Carbon::instance($value);
        if (! is_scalar($value)) return null;
        $text = trim((string) $value);
        if ($text === '') return null;
        foreach (['d/m/Y', 'Y-m-d', 'd/m/Y H:i:s', 'Y-m-d H:i:s'] as $format) {
            try { $date = Carbon::createFromFormat($format, $text); if ($date !== false) return $date; } catch (\Throwable) {}
        }
        try { return Carbon::parse($text); } catch (\Throwable) { return null; }
    }

    private function value(PricingResult $item, string $key, int $index): mixed
    {
        $quantity = is_numeric($item->so_luong) ? (float) $item->so_luong : null;
        $unitPrice = is_numeric($item->don_gia) ? (float) $item->don_gia : null;
        return match ($key) {
            'stt' => $index, 'stt_tt20_2022' => $item->stt_tt20_2022, 'ten_thuoc' => $item->ten_thuoc, 'nhom_thuoc' => $this->medicineGroupNumber($item->nhom_thuoc), 'ten_hoat_chat' => $item->ten_hoat_chat, 'nong_do' => $item->nong_do, 'duong_dung' => $item->duong_dung, 'dang_bao_che' => $item->dang_bao_che, 'don_vi_tinh' => $item->don_vi_tinh, 'quy_cach_dong_goi' => $item->quy_cach_dong_goi, 'gdklh_gpnk' => $item->gdklh_gpnk, 'han_dung' => $item->han_dung, 'ten_co_so_san_xuat' => $item->ten_co_so_san_xuat, 'nuoc_san_xuat' => $item->nuoc_san_xuat, 'don_gia' => $unitPrice, 'gia_kk_kkl' => is_numeric($item->gia_kk_kkl) ? (float) $item->gia_kk_kkl : null, 'don_gia_vat' => is_numeric($item->don_gia_vat) ? (float) $item->don_gia_vat : null, 'so_luong' => $quantity, 'thanh_tien' => $quantity !== null && $unitPrice !== null ? $quantity * $unitPrice : null, 'winning_code' => implode('; ', array_values(array_filter(array_map('strval', (array) $item->winning_code)))), 'winning_name' => implode('; ', array_values(array_filter(array_map('strval', (array) $item->winning_name)))), 'ten_cdt_bmt' => $item->ten_cdt_bmt, 'ma_cdt' => $item->ma_cdt, 'ma_tbmt' => $item->ma_tbmt, 'bid_form' => $item->bid_form, 'dia_diem' => $this->locations($item), 'so_quyet_dinh' => $item->so_quyet_dinh, 'ngay_ban_hanh_quyet_dinh' => $item->ngay_ban_hanh_quyet_dinh?->format('d/m/Y'), 'ngay_dang_tai_kqlcnt' => $item->ngay_dang_tai_kqlcnt?->format('d/m/Y'), 'so_nha_thau_tham_du' => is_numeric($item->so_nha_thau_tham_du) ? (float) $item->so_nha_thau_tham_du : null, 'type' => $item->type, 'tab' => $item->tab, 'medicines' => $item->medicines, 'synced_at' => $item->synced_at?->format('d/m/Y H:i:s'), default => null,
        };
    }

    private function locations(PricingResult $item): ?string
    {
        $locations = collect((array) $item->dia_diem)->map(fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '')->filter()->values()->implode('; ');
        return $locations !== '' ? $locations : null;
    }

    private function medicineGroupNumber(mixed $value): ?string
    {
        if (! is_scalar($value)) return null;
        preg_match('/\d+/', (string) $value, $matches);
        return $matches[0] ?? null;
    }
}
