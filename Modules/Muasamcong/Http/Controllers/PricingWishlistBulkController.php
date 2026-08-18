<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Muasamcong\Models\PricingWishlist;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PricingWishlistBulkController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1', 'max:2000'],
            'selected_ids.*' => ['required', 'integer', 'min:1'],
        ]);

        $deleted = PricingWishlist::query()
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->whereIn('id', array_values(array_unique($validated['selected_ids'])))
            ->delete();

        return back()->with('success', "Đã xóa {$deleted} thuốc khỏi Wishlist.");
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1', 'max:2000'],
            'selected_ids.*' => ['required', 'integer', 'min:1'],
        ]);

        $items = PricingWishlist::query()
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->whereIn('id', array_values(array_unique($validated['selected_ids'])))
            ->orderByDesc('updated_at')
            ->get();

        abort_if($items->isEmpty(), 422, 'Không còn dữ liệu Wishlist hợp lệ để xuất Excel.');

        $rows = $items->values()->map(function (PricingWishlist $wishlist, int $index): array {
            $item = is_array($wishlist->snapshot) ? $wishlist->snapshot : [];
            $quantity = is_numeric($item['soLuong'] ?? null) ? (float) $item['soLuong'] : null;
            $unitPrice = is_numeric($item['donGia'] ?? null) ? (float) $item['donGia'] : null;

            return [
                'STT' => $index + 1,
                'Tên thuốc' => $item['tenThuoc'] ?? $wishlist->medicine_name,
                'Nhóm thuốc' => $item['nhomThuoc'] ?? $item['groupMedicine'] ?? $wishlist->medicine_group,
                'Hoạt chất' => $item['tenHoatChat'] ?? $wishlist->active_ingredient,
                'Nồng độ / Hàm lượng' => $item['nongDo'] ?? $wishlist->strength,
                'Đường dùng' => $item['duongDung'] ?? null,
                'Dạng bào chế' => $item['dangBaoChe'] ?? null,
                'Đơn vị tính' => $item['donViTinh'] ?? null,
                'Giá trúng thầu' => $unitPrice,
                'Số lượng' => $quantity,
                'Thành tiền' => $quantity !== null && $unitPrice !== null ? $quantity * $unitPrice : null,
                'Mã nhà thầu trúng' => $this->joinValues($item['winningCode'] ?? []),
                'Đơn vị trúng thầu' => $this->joinValues($item['winningName'] ?? []),
                'Chủ đầu tư / Bên mời thầu' => $item['tenCdtBmt'] ?? null,
                'Mã TBMT' => $item['maTbmt'] ?? $wishlist->ma_tbmt,
                'Số quyết định' => $item['soQuyetDinh'] ?? null,
                'Ngày quyết định' => $item['ngayBanHanhQuyetDinh'] ?? null,
                'Ngày đăng KQLCNT' => $item['ngayDangTaiKqlcnt'] ?? null,
                'Cơ sở sản xuất' => $item['tenCoSoSanXuat'] ?? null,
                'Nước sản xuất' => $item['nuocSanXuat'] ?? null,
                'Quy cách đóng gói' => $item['quyCachDongGoi'] ?? null,
                'Hạn dùng' => $item['hanDung'] ?? null,
                'Địa điểm' => is_scalar($item['diaDiem'] ?? null) ? (string) $item['diaDiem'] : null,
                'Số nhà thầu tham dự' => $item['soNhaThauThamDu'] ?? null,
                'Từ khóa lúc lưu' => $wishlist->search_keyword,
                'Theo dõi từ' => $wishlist->created_at?->format('d/m/Y H:i:s'),
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
            'Mã TBMT' => null,
            'Số quyết định' => null,
            'Ngày quyết định' => null,
            'Ngày đăng KQLCNT' => null,
            'Cơ sở sản xuất' => null,
            'Nước sản xuất' => null,
            'Quy cách đóng gói' => null,
            'Hạn dùng' => null,
            'Địa điểm' => null,
            'Số nhà thầu tham dự' => null,
            'Từ khóa lúc lưu' => null,
            'Theo dõi từ' => null,
        ]);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'msc-wishlist-');
        abort_if($temporaryPath === false, 500, 'Không thể tạo file Excel tạm.');
        $excelPath = $temporaryPath.'.xlsx';
        @unlink($temporaryPath);

        (new FastExcel($rows))->export($excelPath);

        return response()
            ->download($excelPath, 'Muasamcong-Wishlist-'.now()->format('Ymd-His').'.xlsx', [
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->deleteFileAfterSend(true);
    }

    private function joinValues(mixed $values): string
    {
        return implode('; ', array_values(array_filter(array_map(
            static fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '',
            (array) $values
        ))));
    }
}
