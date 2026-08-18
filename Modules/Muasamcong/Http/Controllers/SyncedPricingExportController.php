<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Muasamcong\Models\PricingResult;
use Rap2hpoutre\FastExcel\FastExcel;
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

        $items = PricingResult::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        abort_if($items->isEmpty(), 422, 'Không tìm thấy dữ liệu đồng bộ đã chọn để xuất Excel.');

        $rows = $items->values()->map(function (PricingResult $item, int $index): array {
            $quantity = is_numeric($item->so_luong) ? (float) $item->so_luong : null;
            $unitPrice = is_numeric($item->don_gia) ? (float) $item->don_gia : null;
            $amount = $quantity !== null && $unitPrice !== null ? $quantity * $unitPrice : null;
            $locations = collect((array) $item->dia_diem)
                ->map(fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '')
                ->filter()
                ->values()
                ->implode('; ');

            return [
                'STT' => $index + 1,
                'Tên thuốc' => $item->ten_thuoc,
                'Nhóm thuốc' => $item->nhom_thuoc,
                'Hoạt chất' => $item->ten_hoat_chat,
                'Nồng độ / Hàm lượng' => $item->nong_do,
                'Đường dùng' => $item->duong_dung,
                'Dạng bào chế' => $item->dang_bao_che,
                'Đơn vị tính' => $item->don_vi_tinh,
                'Giá trúng thầu' => $unitPrice,
                'Số lượng' => $quantity,
                'Thành tiền' => $amount,
                'Mã nhà thầu trúng' => implode('; ', array_values(array_filter(array_map('strval', (array) $item->winning_code)))),
                'Đơn vị trúng thầu' => implode('; ', array_values(array_filter(array_map('strval', (array) $item->winning_name)))),
                'Chủ đầu tư / Bên mời thầu' => $item->ten_cdt_bmt,
                'Mã chủ đầu tư' => $item->ma_cdt,
                'Mã TBMT' => $item->ma_tbmt,
                'Hình thức dự thầu' => $item->bid_form,
                'Địa điểm' => $locations !== '' ? $locations : null,
                'Số quyết định' => $item->so_quyet_dinh,
                'Ngày quyết định' => $item->ngay_ban_hanh_quyet_dinh?->format('d/m/Y'),
                'Ngày đăng KQLCNT' => $item->ngay_dang_tai_kqlcnt?->format('d/m/Y'),
                'Hạn dùng' => $item->han_dung,
                'Cơ sở sản xuất' => $item->ten_co_so_san_xuat,
                'Nước sản xuất' => $item->nuoc_san_xuat,
                'Quy cách đóng gói' => $item->quy_cach_dong_goi,
                'Số nhà thầu tham dự' => is_numeric($item->so_nha_thau_tham_du) ? (float) $item->so_nha_thau_tham_du : null,
                'GĐKLH / GPNK' => $item->gdklh_gpnk,
                'Loại' => $item->type,
                'Tab' => $item->tab,
                'Medicines' => $item->medicines,
                'Đồng bộ lúc' => $item->synced_at?->format('d/m/Y H:i:s'),
            ];
        });

        $rows->push([
            'STT' => null,
            'Tên thuốc' => 'TỔNG CỘNG',
            'Nhóm thuốc' => null,
            'Hoạt chất' => null,
            'Nồng độ / Hàm lượng' => null,
            'Đường dùng' => null,
            'Dạng bào chế' => null,
            'Đơn vị tính' => null,
            'Giá trúng thầu' => null,
            'Số lượng' => $rows->sum(fn (array $row): float => is_numeric($row['Số lượng'] ?? null) ? (float) $row['Số lượng'] : 0),
            'Thành tiền' => $rows->sum(fn (array $row): float => is_numeric($row['Thành tiền'] ?? null) ? (float) $row['Thành tiền'] : 0),
            'Mã nhà thầu trúng' => null,
            'Đơn vị trúng thầu' => null,
            'Chủ đầu tư / Bên mời thầu' => null,
            'Mã chủ đầu tư' => null,
            'Mã TBMT' => null,
            'Hình thức dự thầu' => null,
            'Địa điểm' => null,
            'Số quyết định' => null,
            'Ngày quyết định' => null,
            'Ngày đăng KQLCNT' => null,
            'Hạn dùng' => null,
            'Cơ sở sản xuất' => null,
            'Nước sản xuất' => null,
            'Quy cách đóng gói' => null,
            'Số nhà thầu tham dự' => null,
            'GĐKLH / GPNK' => null,
            'Loại' => null,
            'Tab' => null,
            'Medicines' => null,
            'Đồng bộ lúc' => null,
        ]);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'msc-synced-pricing-');
        abort_if($temporaryPath === false, 500, 'Không thể tạo file Excel tạm.');
        $excelPath = $temporaryPath.'.xlsx';
        @unlink($temporaryPath);

        (new FastExcel($rows))->export($excelPath);

        return response()
            ->download($excelPath, 'Muasamcong-Danh-sach-da-dong-bo-'.now()->format('Ymd-His').'.xlsx', [
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->deleteFileAfterSend(true);
    }
}
