<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\PricingWishlist;
use Modules\Muasamcong\Services\PricingSearchSnapshotService;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class MuasamcongController extends Controller
{
    public function index(): View
    {
        return view('Muasamcong::muasamcong');
    }

    public function contractors(): View
    {
        return view('Muasamcong::contractors');
    }

    public function contractorSearches(): View
    {
        return view('Muasamcong::contractor-searches');
    }

    public function contractorSearchDetail(ContractorSearch $contractorSearch): View
    {
        return view('Muasamcong::contractors', compact('contractorSearch'));
    }

    public function manualContractorLots(string $contractorCode, string $notifyNo): View
    {
        $contractorCode = mb_strtolower(trim($contractorCode));
        $notifyNo = trim($notifyNo);
        abort_unless(preg_match('/^vn\d+$/', $contractorCode) === 1, 404);
        abort_unless(preg_match('/^[A-Za-z0-9_-]+$/', $notifyNo) === 1, 404);

        $lots = ContractorManualLot::query()
            ->where('contractor_code', $contractorCode)
            ->where('notify_no', $notifyNo)
            ->orderBy('lot_no')
            ->orderBy('id')
            ->get();
        abort_if($lots->isEmpty(), 404, 'Chưa có danh mục lô do người dùng xác nhận.');

        $contractorName = ContractorSearch::query()
            ->where('contractor_code', $contractorCode)
            ->value('contractor_name');

        return view('Muasamcong::manual-contractor-lots', compact('lots', 'contractorCode', 'contractorName', 'notifyNo'));
    }

    public function downloadManualContractorLots(string $contractorCode, string $notifyNo): BinaryFileResponse
    {
        $contractorCode = mb_strtolower(trim($contractorCode));
        $notifyNo = trim($notifyNo);
        abort_unless(preg_match('/^vn\d+$/', $contractorCode) === 1, 404);
        abort_unless(preg_match('/^[A-Za-z0-9_-]+$/', $notifyNo) === 1, 404);

        $lots = ContractorManualLot::query()
            ->where('contractor_code', $contractorCode)
            ->where('notify_no', $notifyNo)
            ->orderBy('lot_no')
            ->orderBy('id')
            ->get();
        abort_if($lots->isEmpty(), 404, 'Chưa có danh mục lô / thuốc đã lưu.');

        $savedContractorName = ContractorSearch::query()
            ->where('contractor_code', $contractorCode)
            ->value('contractor_name');

        $rows = $lots->values()->map(function (ContractorManualLot $lot, int $index) use ($notifyNo, $contractorCode, $savedContractorName): array {
            $raw = is_array($lot->raw_payload) ? $lot->raw_payload : [];
            $source = is_array($raw['raw_payload'] ?? null) ? $raw['raw_payload'] : $raw;
            $quantity = is_numeric($lot->quantity) ? (float) $lot->quantity : null;
            $winningUnitPrice = is_numeric($raw['winning_unit_price'] ?? null)
                ? (float) $raw['winning_unit_price']
                : (is_numeric($source['donGia'] ?? null)
                    ? (float) $source['donGia']
                    : (is_numeric($lot->lot_price) ? (float) $lot->lot_price : null));
            $amount = $quantity !== null && $winningUnitPrice !== null
                ? $quantity * $winningUnitPrice
                : (is_numeric($lot->plan_amount) ? (float) $lot->plan_amount : null);
            $winningCodes = (array) ($source['winningCode'] ?? []);
            $winningNames = (array) ($source['winningName'] ?? []);
            $winnerCode = trim((string) ($raw['contractor_code'] ?? ''));
            $winnerName = trim((string) ($raw['contractor_name'] ?? ''));

            if ($winnerCode === '') {
                $winnerCode = implode('; ', array_values(array_filter(array_map(
                    static fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '',
                    $winningCodes
                ))));
            }
            if ($winnerCode === '') {
                $winnerCode = $contractorCode;
            }

            if ($winnerName === '') {
                $winnerName = implode('; ', array_values(array_filter(array_map(
                    static fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '',
                    $winningNames
                ))));
            }
            if ($winnerName === '') {
                $winnerName = (string) ($savedContractorName ?: '');
            }

            return [
                'STT' => $index + 1,
                'Tên thuốc' => $lot->medicine_name ?? $raw['medicine_name'] ?? $source['tenThuoc'] ?? null,
                'Nhóm thuốc' => $raw['medicine_group'] ?? $source['nhomThuoc'] ?? null,
                'Hoạt chất' => $lot->active_ingredient ?? $raw['active_ingredient'] ?? $source['tenHoatChat'] ?? null,
                'Nồng độ / Hàm lượng' => $raw['concentration'] ?? $source['nongDo'] ?? null,
                'Đường dùng' => $raw['route'] ?? $source['duongDung'] ?? null,
                'Dạng bào chế' => $raw['dosage_form'] ?? $source['dangBaoChe'] ?? null,
                'Đơn vị tính' => $raw['uom'] ?? $source['donViTinh'] ?? null,
                'Mã thuốc' => $raw['medicine_code'] ?? $source['medicineCode'] ?? null,
                'Mã lô' => $lot->lot_no,
                'Tên lô' => $lot->lot_name,
                'Giá kế hoạch' => is_numeric($lot->price_plan) ? (float) $lot->price_plan : null,
                'Giá trúng thầu' => $winningUnitPrice,
                'Số lượng' => $quantity,
                'Thành tiền' => $amount,
                'Mã nhà thầu trúng' => $winnerCode,
                'Đơn vị trúng thầu' => $winnerName,
                'Chủ đầu tư / Bên mời thầu' => $source['tenCdtBmt'] ?? $raw['investor_name'] ?? null,
                'Mã TBMT' => $notifyNo,
                'Số quyết định' => $raw['decision_no'] ?? $source['soQuyetDinh'] ?? null,
                'Ngày quyết định' => $raw['decision_date'] ?? $source['ngayBanHanhQuyetDinh'] ?? null,
                'Ngày đăng KQLCNT' => $raw['published_at'] ?? $source['ngayDangTaiKqlcnt'] ?? null,
                'Cơ sở sản xuất' => $raw['manufacturer'] ?? $source['tenCoSoSanXuat'] ?? null,
                'Nước sản xuất' => $raw['country'] ?? $source['nuocSanXuat'] ?? null,
                'Nguồn dữ liệu' => match ($lot->source) {
                    'smart_pricing_verified' => 'Smart Pricing xác minh',
                    'kqlcnt_verified' => 'KQLCNT xác minh',
                    default => 'Người dùng xác nhận từ HSMT',
                },
                'Xác nhận / Đồng bộ lúc' => $lot->confirmed_at?->format('d/m/Y H:i:s'),
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
            'Mã thuốc' => null,
            'Mã lô' => null,
            'Tên lô' => null,
            'Giá kế hoạch' => null,
            'Giá trúng thầu' => null,
            'Số lượng' => $rows->sum(fn (array $row): float => is_numeric($row['Số lượng'] ?? null) ? (float) $row['Số lượng'] : 0),
            'Thành tiền' => $rows->sum(fn (array $row): float => is_numeric($row['Thành tiền'] ?? null) ? (float) $row['Thành tiền'] : 0),
            'Mã nhà thầu trúng' => null,
            'Đơn vị trúng thầu' => null,
            'Chủ đầu tư / Bên mời thầu' => null,
            'Mã TBMT' => $notifyNo,
            'Số quyết định' => null,
            'Ngày quyết định' => null,
            'Ngày đăng KQLCNT' => null,
            'Cơ sở sản xuất' => null,
            'Nước sản xuất' => null,
            'Nguồn dữ liệu' => null,
            'Xác nhận / Đồng bộ lúc' => null,
        ]);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'msc-manual-lots-');
        abort_if($temporaryPath === false, 500, 'Không thể tạo file danh mục tạm.');
        $excelPath = $temporaryPath.'.xlsx';
        @unlink($temporaryPath);

        (new FastExcel($rows))->export($excelPath);

        return response()
            ->download($excelPath, "Danh-muc-{$notifyNo}-{$contractorCode}.xlsx", [
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->deleteFileAfterSend(true);
    }

    public function exportSelectedPricing(Request $request, PricingSearchSnapshotService $snapshots): BinaryFileResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
            'selected_ids' => ['required', 'array', 'min:1', 'max:2000'],
            'selected_ids.*' => ['required', 'string', 'max:100'],
        ]);

        $snapshot = $snapshots->find($validated['keyword']);
        abort_if($snapshot === null || ! is_array($snapshot->result_payload), 404, 'Không tìm thấy dữ liệu tra cứu đã lưu để xuất Excel.');

        $items = is_array($snapshot->result_payload['data']['items'] ?? null)
            ? $snapshot->result_payload['data']['items']
            : [];
        $selected = array_fill_keys(array_values(array_unique($validated['selected_ids'])), true);

        $rows = collect($items)
            ->filter(fn (mixed $item): bool => is_array($item)
                && is_string($item['id'] ?? null)
                && isset($selected[$item['id']]))
            ->values()
            ->map(function (array $item, int $index): array {
                $quantity = is_numeric($item['soLuong'] ?? null) ? (float) $item['soLuong'] : null;
                $unitPrice = is_numeric($item['donGia'] ?? null) ? (float) $item['donGia'] : null;

                return [
                    'STT' => $index + 1,
                    'Tên thuốc' => $item['tenThuoc'] ?? null,
                    'Nhóm thuốc' => $item['nhomThuoc'] ?? $item['groupMedicine'] ?? null,
                    'Hoạt chất' => $item['tenHoatChat'] ?? null,
                    'Nồng độ / Hàm lượng' => $item['nongDo'] ?? null,
                    'Đường dùng' => $item['duongDung'] ?? null,
                    'Dạng bào chế' => $item['dangBaoChe'] ?? null,
                    'Đơn vị tính' => $item['donViTinh'] ?? null,
                    'Giá trúng thầu' => $unitPrice,
                    'Số lượng' => $quantity,
                    'Thành tiền' => $quantity !== null && $unitPrice !== null ? $quantity * $unitPrice : null,
                    'Mã nhà thầu trúng' => implode('; ', array_map('strval', (array) ($item['winningCode'] ?? []))),
                    'Đơn vị trúng thầu' => implode('; ', array_map('strval', (array) ($item['winningName'] ?? []))),
                    'Chủ đầu tư / Bên mời thầu' => $item['tenCdtBmt'] ?? null,
                    'Mã TBMT' => $item['maTbmt'] ?? null,
                    'Số quyết định' => $item['soQuyetDinh'] ?? null,
                    'Ngày quyết định' => $item['ngayBanHanhQuyetDinh'] ?? null,
                    'Ngày đăng KQLCNT' => $item['ngayDangTaiKqlcnt'] ?? null,
                ];
            });

        abort_if($rows->isEmpty(), 422, 'Các dòng đã chọn không còn tồn tại trong dữ liệu tra cứu đã lưu.');

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
        ]);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'msc-pricing-selected-');
        abort_if($temporaryPath === false, 500, 'Không thể tạo file Excel tạm.');
        $excelPath = $temporaryPath.'.xlsx';
        @unlink($temporaryPath);

        (new FastExcel($rows))->export($excelPath);

        $slug = Str::slug($validated['keyword']);
        $filename = 'Muasamcong-'.($slug !== '' ? $slug : 'selected').'-'.now()->format('Ymd-His').'.xlsx';

        return response()
            ->download($excelPath, $filename, [
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->deleteFileAfterSend(true);
    }

    public function hsmt(): View
    {
        return view('Muasamcong::hsmt');
    }

    public function synced(): View
    {
        return view('Muasamcong::synced');
    }

    public function wishlist(Request $request): View
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user !== null, 403);

        $keyword = trim((string) $request->query('q', ''));

        $items = PricingWishlist::query()
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($nested) use ($keyword): void {
                    $nested->where('medicine_name', 'like', "%{$keyword}%")
                        ->orWhere('active_ingredient', 'like', "%{$keyword}%")
                        ->orWhere('medicine_group', 'like', "%{$keyword}%")
                        ->orWhere('ma_tbmt', 'like', "%{$keyword}%")
                        ->orWhere('search_keyword', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('Muasamcong::wishlist', compact('items', 'keyword'));
    }

    public function config(): View
    {
        return view('Muasamcong::config');
    }

    public function downloadWindowsSessionTool(): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'PHP Zip extension is required to build the Windows tool package.');

        $sourceDirectory = base_path('Modules/Muasamcong/tools/windows');
        $files = [
            'Muasamcong-Session-Tool.bat',
            'Muasamcong-Session-Tool.ps1',
            'Open-Muasamcong-Chrome.bat',
            'README.md',
        ];

        foreach ($files as $file) {
            abort_unless(is_file($sourceDirectory.DIRECTORY_SEPARATOR.$file), 404, "Windows tool file not found: {$file}");
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'msc-session-tool-');
        abort_if($temporaryPath === false, 500, 'Unable to create temporary Windows tool package.');

        $zipPath = $temporaryPath.'.zip';
        @unlink($temporaryPath);

        $zip = new ZipArchive;
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Unable to create Windows tool package.');

        foreach ($files as $file) {
            $zip->addFile($sourceDirectory.DIRECTORY_SEPARATOR.$file, $file);
        }

        $zip->close();

        return response()
            ->download($zipPath, 'Muasamcong-Session-Tool-Windows.zip', [
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->deleteFileAfterSend(true);
    }
}
