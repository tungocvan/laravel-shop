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
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PharmaMedicineExcelController extends Controller
{
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
    ): BinaryFileResponse {
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.products.export'), 403);

        $data = $request->validate([
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

        $path = $excel->export(['selected_ids' => $ids]);

        return response()->download($excel->exportAbsolutePath($path), 'danh-muc-thuoc.xlsx')
            ->deleteFileAfterSend(true);
    }
}
