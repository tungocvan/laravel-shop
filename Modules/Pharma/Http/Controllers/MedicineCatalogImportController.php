<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Pharma\Exports\MedicineCatalogTemplateExport;
use Modules\Pharma\Models\MedicineImportBatch;
use Modules\Pharma\Models\MedicineImportRow;
use Modules\Pharma\Services\MedicineCatalogImportCommitter;
use Modules\Pharma\Services\MedicineCatalogUploadService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MedicineCatalogImportController extends Controller
{
    public function index(Request $request): View
    {
        $batch = null;
        $rows = null;
        $selectedCount = 0;

        if ($request->filled('batch')) {
            $batch = MedicineImportBatch::query()->findOrFail($request->integer('batch'));
            $rows = $batch->rows()
                ->with(['matchedMedicine', 'matchedVariant'])
                ->when($request->filled('classification'), fn ($query) => $query->where('classification', (string) $request->string('classification')))
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.(string) $request->string('search').'%';
                    $query->where(function ($nested) use ($search) {
                        $nested->where('normalized_payload->name', 'like', $search)
                            ->orWhere('normalized_payload->registration_number', 'like', $search)
                            ->orWhere('normalized_payload->active_ingredients', 'like', $search);
                    });
                })
                ->orderBy('source_row')
                ->paginate($this->perPage($request))
                ->withQueryString();

            $selectedCount = $batch->rows()
                ->where('selected', true)
                ->whereIn('classification', [MedicineImportRow::CLASS_NEW, MedicineImportRow::CLASS_UPDATE])
                ->count();
        }

        return view('Pharma::pages.medicine-import.index', [
            'batch' => $batch,
            'rows' => $rows,
            'selectedCount' => $selectedCount,
            'batches' => MedicineImportBatch::query()->latest('id')->paginate(10, ['*'], 'history_page'),
            'classifications' => [
                MedicineImportRow::CLASS_NEW => 'Mới',
                MedicineImportRow::CLASS_UPDATE => 'Cập nhật',
                MedicineImportRow::CLASS_DUPLICATE => 'Trùng',
                MedicineImportRow::CLASS_CONFLICT => 'Xung đột',
                MedicineImportRow::CLASS_NEEDS_REVIEW => 'Cần rà soát',
            ],
        ]);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new MedicineCatalogTemplateExport, 'medicine-catalog-template.xlsx');
    }

    public function store(Request $request, MedicineCatalogUploadService $service): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:10240'],
        ]);

        $batch = $service->stage($request->file('file'), auth('admin')->id());

        return redirect()->route('admin.pharma.medicines.import.index', ['batch' => $batch->id])
            ->with('success', 'Đã staging và phân loại danh mục thuốc. Chưa ghi dữ liệu vào Medicine Master.');
    }

    public function selection(Request $request, MedicineImportBatch $batch): RedirectResponse
    {
        $validated = $request->validate([
            'visible' => ['required', 'array'],
            'visible.*' => ['integer'],
            'selected' => ['array'],
            'selected.*' => ['integer'],
        ]);

        $visible = collect($validated['visible'])->map(fn ($id) => (int) $id)->unique();
        $selected = collect($validated['selected'] ?? [])->map(fn ($id) => (int) $id)->intersect($visible)->unique();

        $batch->rows()->whereIn('id', $visible->all())->update(['selected' => false]);
        if ($selected->isNotEmpty()) {
            $batch->rows()
                ->whereIn('id', $selected->all())
                ->whereIn('classification', [MedicineImportRow::CLASS_NEW, MedicineImportRow::CLASS_UPDATE])
                ->update(['selected' => true]);
        }

        return back()->with('success', 'Đã lưu lựa chọn của trang hiện tại.');
    }

    public function commit(MedicineImportBatch $batch, MedicineCatalogImportCommitter $committer): RedirectResponse
    {
        $selectedCount = $batch->rows()
            ->where('selected', true)
            ->whereIn('classification', [MedicineImportRow::CLASS_NEW, MedicineImportRow::CLASS_UPDATE])
            ->count();

        if ($selectedCount === 0) {
            return back()->with('error', 'Không có dòng NEW/UPDATE nào được chọn để đồng bộ. Hãy kiểm tra mapping hoặc phân loại staging trước khi commit.');
        }

        $result = $committer->commit($batch);

        return back()
            ->with('success', sprintf(
                'Đã đồng bộ Medicine Master: %d created, %d updated, %d skipped.',
                $result['created'],
                $result['updated'],
                $result['skipped'],
            ))
            ->with('medicine_import_commit_result', [
                'created' => (int) $result['created'],
                'updated' => (int) $result['updated'],
                'skipped' => (int) $result['skipped'],
                'total' => (int) ($result['created'] + $result['updated'] + $result['skipped']),
            ]);
    }

    public function clearHistory(): RedirectResponse
    {
        $deletedBatches = DB::transaction(function (): int {
            MedicineImportRow::query()->delete();

            return MedicineImportBatch::query()->delete();
        });

        return redirect()->route('admin.pharma.medicines.import.index')
            ->with('success', sprintf(
                'Đã xóa %d lịch sử import/staging. Medicine Master đã đồng bộ không bị thay đổi.',
                $deletedBatches,
            ));
    }

    private function perPage(Request $request): int
    {
        $value = $request->integer('per_page', 25);

        return in_array($value, [10, 25, 50, 100], true) ? $value : 25;
    }
}
