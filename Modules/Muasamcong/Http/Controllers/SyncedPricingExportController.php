<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Services\SyncedPricingExportPreferenceService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SyncedPricingExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1', 'max:5000'],
            'selected_ids.*' => ['required', 'integer', 'min:1'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['selected_ids'])));
        $preference = app(SyncedPricingExportPreferenceService::class)->forUser((int) Auth::guard('admin')->id());
        $selectedLookup = array_fill_keys($preference['selected_columns'], true);
        $requestedColumns = array_values(array_filter(
            $preference['column_order'],
            fn (string $key): bool => isset($selectedLookup[$key], SyncedPricingExportPreferenceService::COLUMNS[$key])
        ));

        abort_if($requestedColumns === [], 422, 'Cấu hình xuất chưa chọn cột nào. Hãy mở Cấu hình cột và chọn ít nhất một cột.');

        $items = PricingResult::query()->whereIn('id', $ids)->orderBy('id')->get();
        abort_if($items->isEmpty(), 422, 'Không tìm thấy dữ liệu đồng bộ đã chọn để xuất Excel.');

        $temporaryPath = tempnam(sys_get_temp_dir(), 'msc-synced-pricing-');
        abort_if($temporaryPath === false, 500, 'Không thể tạo file Excel tạm.');
        $excelPath = $temporaryPath.'.xlsx';
        @unlink($temporaryPath);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dữ liệu đồng bộ');

        foreach ($requestedColumns as $columnIndex => $key) {
            $cell = $sheet->getCell([$columnIndex + 1, 1]);
            $cell->setValue(SyncedPricingExportPreferenceService::COLUMNS[$key]['label']);
            $width = $preference['widths'][$key] ?? SyncedPricingExportPreferenceService::COLUMNS[$key]['width'];
            $sheet->getColumnDimension($cell->getColumn())->setAutoSize(false)->setWidth((float) $width);
        }

        $lastHeaderCell = $sheet->getCell([count($requestedColumns), 1])->getCoordinate();
        $sheet->getStyle('A1:'.$lastHeaderCell)->getFont()->setBold(true);
        $sheet->getStyle('A1:'.$lastHeaderCell)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(-1);

        foreach ($items->values() as $rowIndex => $item) {
            $excelRow = $rowIndex + 2;
            foreach ($requestedColumns as $columnIndex => $key) {
                $value = $this->value($item, $key, $rowIndex + 1);
                $cell = $sheet->getCell([$columnIndex + 1, $excelRow]);

                if (in_array($key, ['gdklh_gpnk', 'stt_tt20_2022'], true)) {
                    $cell->setValueExplicit($value === null ? '' : (string) $value, DataType::TYPE_STRING);
                    $cell->getStyle()->getNumberFormat()->setFormatCode('@');
                } else {
                    $cell->setValue($value);
                }
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(-1);
        }

        $lastRow = $items->count() + 1;
        $lastColumn = $sheet->getCell([count($requestedColumns), 1])->getColumn();
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        foreach ($requestedColumns as $columnIndex => $key) {
            $columnLetter = $sheet->getCell([$columnIndex + 1, 1])->getColumn();
            $alignment = $preference['alignments'][$key] ?? SyncedPricingExportPreferenceService::COLUMNS[$key]['align'];
            $horizontal = match ($alignment) {
                'center' => Alignment::HORIZONTAL_CENTER,
                'right' => Alignment::HORIZONTAL_RIGHT,
                default => Alignment::HORIZONTAL_LEFT,
            };
            $sheet->getStyle("{$columnLetter}2:{$columnLetter}{$lastRow}")->getAlignment()
                ->setHorizontal($horizontal)
                ->setWrapText(true);
        }

        $sheet->freezePane('A2');
        (new Xlsx($spreadsheet))->save($excelPath);
        $spreadsheet->disconnectWorksheets();

        return response()->download(
            $excelPath,
            'Muasamcong-Danh-sach-da-dong-bo-'.now()->format('Ymd-His').'.xlsx',
            ['Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']
        )->deleteFileAfterSend(true);
    }

    private function value(PricingResult $item, string $key, int $index): mixed
    {
        $quantity = is_numeric($item->so_luong) ? (float) $item->so_luong : null;
        $unitPrice = is_numeric($item->don_gia) ? (float) $item->don_gia : null;

        return match ($key) {
            'stt' => $index,
            'stt_tt20_2022' => $item->stt_tt20_2022,
            'ten_thuoc' => $item->ten_thuoc,
            'nhom_thuoc' => $this->medicineGroupNumber($item->nhom_thuoc),
            'ten_hoat_chat' => $item->ten_hoat_chat,
            'nong_do' => $item->nong_do,
            'duong_dung' => $item->duong_dung,
            'dang_bao_che' => $item->dang_bao_che,
            'don_vi_tinh' => $item->don_vi_tinh,
            'quy_cach_dong_goi' => $item->quy_cach_dong_goi,
            'gdklh_gpnk' => $item->gdklh_gpnk,
            'han_dung' => $item->han_dung,
            'ten_co_so_san_xuat' => $item->ten_co_so_san_xuat,
            'nuoc_san_xuat' => $item->nuoc_san_xuat,
            'don_gia' => $unitPrice,
            'gia_kk_kkl' => is_numeric($item->gia_kk_kkl) ? (float) $item->gia_kk_kkl : null,
            'don_gia_vat' => is_numeric($item->don_gia_vat) ? (float) $item->don_gia_vat : null,
            'so_luong' => $quantity,
            'thanh_tien' => $quantity !== null && $unitPrice !== null ? $quantity * $unitPrice : null,
            'winning_code' => implode('; ', array_values(array_filter(array_map('strval', (array) $item->winning_code)))),
            'winning_name' => implode('; ', array_values(array_filter(array_map('strval', (array) $item->winning_name)))),
            'ten_cdt_bmt' => $item->ten_cdt_bmt,
            'ma_cdt' => $item->ma_cdt,
            'ma_tbmt' => $item->ma_tbmt,
            'bid_form' => $item->bid_form,
            'dia_diem' => $this->locations($item),
            'so_quyet_dinh' => $item->so_quyet_dinh,
            'ngay_ban_hanh_quyet_dinh' => $item->ngay_ban_hanh_quyet_dinh?->format('d/m/Y'),
            'ngay_dang_tai_kqlcnt' => $item->ngay_dang_tai_kqlcnt?->format('d/m/Y'),
            'so_nha_thau_tham_du' => is_numeric($item->so_nha_thau_tham_du) ? (float) $item->so_nha_thau_tham_du : null,
            'type' => $item->type,
            'tab' => $item->tab,
            'medicines' => $item->medicines,
            'synced_at' => $item->synced_at?->format('d/m/Y H:i:s'),
            default => null,
        };
    }

    private function locations(PricingResult $item): ?string
    {
        $locations = collect((array) $item->dia_diem)
            ->map(fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '')
            ->filter()->values()->implode('; ');

        return $locations !== '' ? $locations : null;
    }

    private function medicineGroupNumber(mixed $value): ?string
    {
        if (! is_scalar($value)) return null;
        preg_match('/\d+/', (string) $value, $matches);
        return $matches[0] ?? null;
    }
}
