<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Modules\Pharma\Services\MedicineCatalogUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Pharma\Services\MedicineCatalog;
use Modules\Pharma\Services\MedicineImportExport;
use Modules\Pharma\Services\MedicineExcelProfileService;
use Modules\Pharma\Services\MedicineConfiguredExcelExporter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PharmaMedicineExcelController extends Controller
{
    public function template(Request $request, ApplicationRegistry $registry): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.products.import'), 403);

        // Match the accepted header aliases in MedicineCatalogImportMapper.
        $headers = [
            'Loại sản phẩm', 'Tên thuốc', 'Tên hoạt chất', 'Nồng độ - Hàm lượng',
            'Dạng bào chế', 'Đường dùng', 'Đơn vị tính', 'Quy cách đóng gói',
            'Giấy phép lưu hành sản phẩm', 'Nhóm thuốc', 'Thuốc KSĐB',
            'Hạn dùng', 'Cơ sở sản xuất', 'Nước sản xuất', 'Giá kê khai',
        ];

        return response()->streamDownload(static function () use ($headers): void {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
            try {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Danh muc thuoc');
                $sheet->fromArray($headers, null, 'A1');
                $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
                $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle("A1:{$lastColumn}1")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('1E3A8A');
                $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()->setWrapText(true);
                $sheet->getRowDimension(1)->setRowHeight(36);
                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastColumn}1");
                foreach (range(1, count($headers)) as $index) {
                    $sheet->getColumnDimensionByColumn($index)->setWidth(24);
                }
                $sheet->getColumnDimension('B')->setWidth(34);
                $sheet->getColumnDimension('I')->setWidth(32);
                (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, 'mau-nhap-danh-muc-thuoc.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function import(Request $request, ApplicationRegistry $registry, MedicineCatalogUploadService $upload): RedirectResponse
    {
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.products.import'), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:10240'],
        ]);

        // Staging is intentionally non-destructive. No Medicine Master writes here.
        $batch = $upload->stage($validated['file'], (int) $user->id);
        $batch->forceFill(['source' => 'clientportal_pharma'])->save();

        return redirect()->route('client.pharma.products')
            ->with('medicine_import_staged', [
                'batch_id' => (int) $batch->id,
                'total' => $batch->rows()->count(),
                'new' => $batch->rows()->where('classification', 'new')->count(),
                'update' => $batch->rows()->where('classification', 'update')->count(),
            ]);
    }

    public function export(
        Request $request,
        ApplicationRegistry $registry,
        MedicineCatalog $catalog,
        MedicineImportExport $excel,
        MedicineExcelProfileService $profiles,
        MedicineConfiguredExcelExporter $configured,
    ): BinaryFileResponse {
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.products.export'), 403);

        $data = $request->validate([
            'profile_id' => ['nullable', 'integer'],
            'ids' => ['nullable', 'array', 'max:10000'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'q' => ['nullable', 'string', 'max:120'],
            'group' => ['nullable', 'string', 'max:100'],
            'product_type' => ['nullable', 'string', 'max:50'],
            'special_control' => ['nullable', 'in:yes,no'],
            'hssp' => ['nullable', 'in:with,without'],
            'supplier_price' => ['nullable', 'in:with,without'],
            'bid_award' => ['nullable', 'in:with,without'],
            'filter' => ['nullable', 'in:awarded,profile,supplier-priced'],
        ]);

        $canPrice = $registry->userCan($user, 'client.pharma.products.supplier-pricing');
        abort_if(! $canPrice && (filled($data['supplier_price'] ?? null) || ($data['filter'] ?? null) === 'supplier-priced'), 403);

        $ids = array_map('intval', $data['ids'] ?? []);
        if ($ids === []) {
            // Export every matching Medicine, not merely the currently loaded PWA page.
            $page = 1;
            do {
                $result = $catalog->browse(
                    search: $data['q'] ?? null,
                    perPage: 100,
                    page: $page,
                    filter: $data['filter'] ?? null,
                    allowSupplierPricing: $canPrice,
                    circularGroup: $data['group'] ?? null,
                    productType: $data['product_type'] ?? null,
                    specialControl: $data['special_control'] ?? null,
                    hssp: $data['hssp'] ?? null,
                    supplierPrice: $data['supplier_price'] ?? null,
                    bidAward: $data['bid_award'] ?? null,
                );
                foreach ($result->items() as $item) {
                    $ids[$item->medicineId] = $item->medicineId;
                }
                if (count($ids) > 10000) {
                    throw ValidationException::withMessages(['ids' => 'Kết quả vượt giới hạn 10.000 thuốc. Vui lòng thu hẹp bộ lọc.']);
                }
                $page++;
            } while ($result->hasMorePages());
            $ids = array_values($ids);
        }

        if ($ids === []) {
            throw ValidationException::withMessages(['ids' => 'Không có thuốc phù hợp để xuất Excel.']);
        }

        $profile = $profiles->forUser((int) $user->id, $data['profile_id'] ?? null, 'web');
        $path = $configured->export($ids, $profile, $canPrice);

        return response()->download($path, 'danh-muc-thuoc-theo-mau.xlsx')
            ->deleteFileAfterSend(true);
    }
}
