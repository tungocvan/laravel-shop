<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\KqlcntImportBatch;
use Modules\Muasamcong\Models\KqlcntRecord;
use Modules\Muasamcong\Services\ContractorKqlcntExportService;
use Modules\Muasamcong\Services\KqlcntHistoricalImportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractorKqlcntRecoveryController extends Controller
{
    public function index(ContractorSearch $contractorSearch, KqlcntHistoricalImportService $importer): View
    {
        return $this->view($contractorSearch, null, $importer);
    }

    public function upload(Request $request, ContractorSearch $contractorSearch, KqlcntHistoricalImportService $importer): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $batch = $importer->stage($contractorSearch, $validated['file']);

        return redirect()
            ->route('muasamcong.contractors.kqlcnt-recovery.batch', [$contractorSearch, $batch])
            ->with('status', 'Đã tải file lên. Kiểm tra mapping trước khi preview dữ liệu.');
    }

    public function batch(ContractorSearch $contractorSearch, KqlcntImportBatch $batch, KqlcntHistoricalImportService $importer): View
    {
        $this->assertBatchScope($contractorSearch, $batch);

        return $this->view($contractorSearch, $batch, $importer);
    }

    public function preview(Request $request, ContractorSearch $contractorSearch, KqlcntImportBatch $batch, KqlcntHistoricalImportService $importer): RedirectResponse
    {
        $this->assertBatchScope($contractorSearch, $batch);
        $validated = $request->validate([
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'string', 'max:255'],
            'target_notify_no' => ['nullable', 'string', 'max:64'],
        ]);

        $headers = collect((array) $batch->headers);
        $mapping = collect($validated['mapping'])
            ->filter(fn ($header) => is_string($header) && $header !== '' && $headers->contains($header))
            ->all();

        $importer->preview($batch, $mapping, $validated['target_notify_no'] ?? null);

        return redirect()
            ->route('muasamcong.contractors.kqlcnt-recovery.batch', [$contractorSearch, $batch])
            ->with('status', 'Preview đã được cập nhật. Chỉ xác nhận khi số liệu và conflict đúng như mong muốn.');
    }

    public function confirm(Request $request, ContractorSearch $contractorSearch, KqlcntImportBatch $batch, KqlcntHistoricalImportService $importer): RedirectResponse
    {
        $this->assertBatchScope($contractorSearch, $batch);
        $validated = $request->validate([
            'overwrite_conflicts' => ['nullable', 'boolean'],
        ]);

        $importer->confirm($batch, (bool) ($validated['overwrite_conflicts'] ?? false));

        return redirect()
            ->route('muasamcong.contractors.kqlcnt-recovery', $contractorSearch)
            ->with('status', 'Import KQLCNT đã được xác nhận và lưu vào cơ sở dữ liệu.');
    }

    public function export(Request $request, ContractorSearch $contractorSearch, ContractorKqlcntExportService $exporter): BinaryFileResponse
    {
        $validated = $request->validate([
            'notify_nos' => ['nullable', 'array', 'max:5000'],
            'notify_nos.*' => ['required', 'string', 'max:64'],
        ]);

        return $exporter->download($contractorSearch, $validated['notify_nos'] ?? []);
    }

    private function view(ContractorSearch $search, ?KqlcntImportBatch $batch, KqlcntHistoricalImportService $importer): View
    {
        $items = $search->items()->orderByDesc('created_date')->get();
        $records = KqlcntRecord::query()
            ->where('contractor_code', $search->contractor_code)
            ->whereIn('notify_no', $items->pluck('notify_no')->filter())
            ->get()->keyBy('notify_no');

        return view('Muasamcong::contractor-kqlcnt-recovery', [
            'contractorSearch' => $search,
            'items' => $items,
            'records' => $records,
            'batch' => $batch,
            'fieldLabels' => $importer->fieldLabels(),
        ]);
    }

    private function assertBatchScope(ContractorSearch $search, KqlcntImportBatch $batch): void
    {
        abort_unless((int) $batch->contractor_search_id === (int) $search->id, 404);
    }
}
