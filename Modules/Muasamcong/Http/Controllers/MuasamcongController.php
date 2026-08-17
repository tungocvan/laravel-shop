<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\PricingWishlist;
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
        abort_if($lots->isEmpty(), 404, 'Chưa có danh mục lô do người dùng xác nhận.');

        $rows = $lots->map(fn (ContractorManualLot $lot): array => [
            'Mã TBMT' => $notifyNo,
            'CONTRACTOR_CODE' => $contractorCode,
            'Mã lô' => $lot->lot_no,
            'Tên lô' => $lot->lot_name,
            'Tên thuốc' => $lot->medicine_name,
            'Hoạt chất' => $lot->active_ingredient,
            'Số lượng' => $lot->quantity,
            'Giá KH' => $lot->price_plan,
            'SL x Giá KH' => $lot->plan_amount,
            'Giá lô' => $lot->lot_price,
            'Nguồn' => 'Người dùng xác nhận',
            'Xác nhận lúc' => $lot->confirmed_at?->format('d/m/Y H:i:s'),
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
