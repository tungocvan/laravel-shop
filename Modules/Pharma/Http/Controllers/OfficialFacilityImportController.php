<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\OfficialFacilityImportBatch;
use Modules\Pharma\Models\OfficialFacilityImportRow;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialFacilityConflictResolver;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialFacilityImportService;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialFacilityImportSummary;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialFacilityPartnerImporter;
use Throwable;

class OfficialFacilityImportController extends Controller
{
    public function index(Request $request): View
    {
        $batch = null;
        $rows = null;

        if ($request->filled('batch')) {
            $batch = OfficialFacilityImportBatch::query()->findOrFail($request->integer('batch'));
            $rows = $batch->rows()
                ->with(['matchedPartner'])
                ->when($request->filled('classification'), fn ($query) => $query->where('classification', $request->string('classification')))
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';
                    $query->where(function ($nested) use ($search) {
                        $nested->where('facility_name', 'like', $search)
                            ->orWhere('external_id', 'like', $search)
                            ->orWhere('tax_code', 'like', $search);
                    });
                })
                ->orderBy('row_number')
                ->paginate($this->perPage($request))
                ->withQueryString();
        }

        return view('Pharma::pages.official-facilities.import', [
            'batch' => $batch,
            'rows' => $rows,
            'batches' => OfficialFacilityImportBatch::query()->latest('id')->paginate(10, ['*'], 'history_page'),
            'partners' => Partner::query()->where('legal_type', 'hospital')->where('status', 'active')->orderBy('name')->limit(200)->get(['id', 'name', 'tax_code', 'province_code']),
        ]);
    }

    public function store(Request $request, OfficialFacilityImportService $service): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'max:50'],
            'province_code' => ['required', 'string', 'max:20'],
            'source_province_code' => ['nullable', 'string', 'max:50'],
            'source_date' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:10240'],
        ]);

        $batch = $service->stage($request->file('file'), $validated, auth('admin')->id());

        return redirect()->route('admin.pharma.official-facilities.index', ['batch' => $batch->id])
            ->with('success', 'Đã staging và phân loại toàn bộ tệp. Chưa có Partner nào được ghi ở bước upload.');
    }

    public function selection(Request $request, OfficialFacilityImportBatch $batch, OfficialFacilityImportSummary $summary): RedirectResponse
    {
        $validated = $request->validate(['selected' => ['array'], 'selected.*' => ['integer']]);
        $selected = collect($validated['selected'] ?? [])->map(fn ($id) => (int) $id)->all();

        $batch->rows()->whereNull('imported_at')->update(['is_selected' => false]);
        if ($selected !== []) {
            $batch->rows()
                ->whereIn('id', $selected)
                ->where('classification', '!=', 'INVALID')
                ->whereNull('import_status')
                ->update(['is_selected' => true]);
        }
        $summary->refresh($batch);

        return back()->with('success', 'Đã lưu lựa chọn. Chỉ các dòng được chọn mới đủ điều kiện import.');
    }

    public function resolve(Request $request, OfficialFacilityImportRow $row, OfficialFacilityConflictResolver $resolver): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:link,create,skip'],
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['action'] === 'link' && empty($validated['partner_id'])) {
            return back()->withErrors(['partner_id' => 'Phải chọn Partner khi liên kết.']);
        }

        $resolver->resolve($row, $validated['action'], $validated['partner_id'] ?? null, auth('admin')->id(), $validated['note'] ?? null);

        return back()->with('success', 'Đã xử lý dòng xung đột.');
    }

    public function importSelected(OfficialFacilityImportBatch $batch, OfficialFacilityPartnerImporter $importer, OfficialFacilityImportSummary $summary): RedirectResponse
    {
        $batch->update(['status' => 'IMPORTING', 'started_at' => $batch->started_at ?? now(), 'completed_at' => null]);

        $rows = $batch->rows()->where('is_selected', true)->whereNull('imported_at')->whereNull('import_status')->orderBy('id')->get();
        foreach ($rows as $row) {
            try {
                $importer->import($row);
            } catch (Throwable $exception) {
                $row->update(['import_status' => 'FAILED', 'error_message' => $exception->getMessage()]);
            }
        }

        $summary->refresh($batch);
        $batch->refresh()->update([
            'status' => $batch->failed_count > 0 ? 'COMPLETED_WITH_ERRORS' : 'COMPLETED',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Đã xử lý các dòng được chọn. Kiểm tra thống kê CREATED/LINKED/FAILED trên batch.');
    }

    private function perPage(Request $request): int
    {
        $value = $request->integer('per_page', 25);

        return in_array($value, [10, 25, 50, 100], true) ? $value : 25;
    }
}
