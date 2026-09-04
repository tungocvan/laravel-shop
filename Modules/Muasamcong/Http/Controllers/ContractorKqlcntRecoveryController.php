<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Muasamcong\Exports\KqlcntArraySheetExport;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Models\KqlcntImportBatch;
use Modules\Muasamcong\Models\KqlcntRecord;
use Modules\Muasamcong\Services\ContractorAwardCatalogService;
use Modules\Muasamcong\Services\ContractorAwardEnrichmentService;
use Modules\Muasamcong\Services\ContractorKqlcntExportService;
use Modules\Muasamcong\Services\KqlcntHistoricalImportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ContractorKqlcntRecoveryController extends Controller
{
    public function index(ContractorSearch $contractorSearch, KqlcntHistoricalImportService $importer, ContractorAwardCatalogService $catalog): View
    {
        return $this->view($contractorSearch, null, $importer, $catalog);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new KqlcntArraySheetExport('Danh_muc_trung_thau', $this->importHeadings(), []), 'Mau-Import-KQLCNT.xlsx');
    }

    public function supplement(ContractorSearch $contractorSearch, string $notifyNo, KqlcntHistoricalImportService $importer, ContractorAwardCatalogService $catalog): View
    {
        $item = $this->assertNotifyScope($contractorSearch, $notifyNo);
        $record = KqlcntRecord::query()->where('contractor_code', $contractorSearch->contractor_code)->where('notify_no', $notifyNo)->first();
        $rows = $this->supplementRows($contractorSearch, $notifyNo, $catalog, $record);

        return view('Muasamcong::contractor-kqlcnt-supplement', compact('contractorSearch', 'item', 'record', 'rows'));
    }

    public function supplementDownload(ContractorSearch $contractorSearch, string $notifyNo, ContractorAwardCatalogService $catalog): BinaryFileResponse
    {
        $this->assertNotifyScope($contractorSearch, $notifyNo);
        $record = KqlcntRecord::query()->where('contractor_code', $contractorSearch->contractor_code)->where('notify_no', $notifyNo)->first();
        $rows = $this->supplementRows($contractorSearch, $notifyNo, $catalog, $record);

        return Excel::download(
            new KqlcntArraySheetExport('Danh_muc_trung_thau', $this->importHeadings(), $rows),
            'Bo-sung-KQLCNT-'.$notifyNo.'.xlsx'
        );
    }

    public function supplementUpload(Request $request, ContractorSearch $contractorSearch, string $notifyNo, KqlcntHistoricalImportService $importer): RedirectResponse
    {
        $this->assertNotifyScope($contractorSearch, $notifyNo);
        $validated = $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);
        $batch = $importer->stage($contractorSearch, $validated['file']);
        $importer->preview($batch, (array) $batch->mapping, $notifyNo);

        return redirect()->route('muasamcong.contractors.kqlcnt-recovery.batch', [$contractorSearch, $batch])
            ->with('status', 'Đã đọc file bổ sung cho '.$notifyNo.'. TBMT đã được khóa; kiểm tra Preview trước khi xác nhận Import.');
    }

    public function upload(Request $request, ContractorSearch $contractorSearch, KqlcntHistoricalImportService $importer): RedirectResponse
    {
        $validated = $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);
        $batch = $importer->stage($contractorSearch, $validated['file']);

        return redirect()->route('muasamcong.contractors.kqlcnt-recovery.batch', [$contractorSearch, $batch])
            ->with('status', 'Đã tải file lên. Kiểm tra mapping trước khi preview dữ liệu.');
    }

    public function batch(ContractorSearch $contractorSearch, KqlcntImportBatch $batch, KqlcntHistoricalImportService $importer, ContractorAwardCatalogService $catalog): View
    {
        $this->assertBatchScope($contractorSearch, $batch);

        return $this->view($contractorSearch, $batch, $importer, $catalog);
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
        $mapping = collect($validated['mapping'])->filter(fn ($header) => is_string($header) && $header !== '' && $headers->contains($header))->all();
        $importer->preview($batch, $mapping, $validated['target_notify_no'] ?? null);

        return redirect()->route('muasamcong.contractors.kqlcnt-recovery.batch', [$contractorSearch, $batch])
            ->with('status', 'Preview đã được cập nhật. Chỉ xác nhận khi số liệu và conflict đúng như mong muốn.');
    }

    public function confirm(Request $request, ContractorSearch $contractorSearch, KqlcntImportBatch $batch, KqlcntHistoricalImportService $importer): RedirectResponse
    {
        $this->assertBatchScope($contractorSearch, $batch);
        $validated = $request->validate(['overwrite_conflicts' => ['nullable', 'boolean']]);
        $importer->confirm($batch, (bool) ($validated['overwrite_conflicts'] ?? false));

        return redirect()->route('muasamcong.contractors.kqlcnt-recovery', $contractorSearch)
            ->with('status', 'Import KQLCNT đã được xác nhận và lưu vào cơ sở dữ liệu.');
    }

    public function enrich(Request $request, ContractorSearch $contractorSearch, ContractorAwardEnrichmentService $enrichment): RedirectResponse
    {
        $validated = $request->validate(['notify_nos' => ['required', 'array', 'min:1', 'max:50'], 'notify_nos.*' => ['required', 'string', 'max:64']]);
        $scope = $contractorSearch->items()->pluck('notify_no')->map(fn ($value) => trim((string) $value))->filter()->unique();
        $notifyNos = collect($validated['notify_nos'])->map(fn ($value) => trim((string) $value))->filter()->unique()->values();
        abort_if($notifyNos->diff($scope)->isNotEmpty(), 422, 'Có Mã TBMT không thuộc lịch sử nhà thầu này.');
        $synced = 0;
        $failed = [];
        $truncated = false;
        foreach ($notifyNos as $notifyNo) {
            try {
                $result = $enrichment->sync($notifyNo, $contractorSearch->contractor_code);
                $synced += (int) ($result['count'] ?? 0);
                $truncated = $truncated || (bool) ($result['truncated'] ?? false);
            } catch (Throwable $e) {
                report($e);
                $failed[] = $notifyNo;
            }
        }
        $message = 'Đã đồng bộ chi tiết KQLCNT/Smart Pricing: '.number_format($synced).' dòng thuốc.';
        if ($truncated) {
            $message .= ' Có TBMT vượt giới hạn số trang cấu hình.';
        }
        if ($failed !== []) {
            $message .= ' Không đồng bộ được: '.implode(', ', $failed).'.';
        }

        return redirect()->route('muasamcong.contractors.kqlcnt-recovery', $contractorSearch)->with('status', $message);
    }

    public function export(Request $request, ContractorSearch $contractorSearch, ContractorKqlcntExportService $exporter): BinaryFileResponse
    {
        $validated = $request->validate(['notify_nos' => ['nullable', 'array', 'max:5000'], 'notify_nos.*' => ['required', 'string', 'max:64']]);

        return $exporter->download($contractorSearch, $validated['notify_nos'] ?? []);
    }

    private function view(ContractorSearch $search, ?KqlcntImportBatch $batch, KqlcntHistoricalImportService $importer, ContractorAwardCatalogService $catalog): View
    {
        $items = $search->items()->orderByDesc('created_date')->get();
        $notifyNos = $items->pluck('notify_no')->filter()->values();
        $records = KqlcntRecord::query()->where('contractor_code', $search->contractor_code)->whereIn('notify_no', $notifyNos)->get()->keyBy('notify_no');
        $importCounts = KqlcntAwardItem::query()->where('contractor_code', $search->contractor_code)->whereIn('notify_no', $notifyNos)->selectRaw('notify_no, COUNT(*) as aggregate')->groupBy('notify_no')->pluck('aggregate', 'notify_no');
        $catalogRows = $catalog->rows($search->contractor_code, $notifyNos);
        $savedCounts = $catalogRows->groupBy('notify_no')->map(fn ($rows) => $rows->count());
        $enrichedSavedCounts = $catalogRows->filter(fn (array $row): bool => str_contains((string) ($row['source'] ?? ''), 'SMART_PRICING') || str_contains((string) ($row['source'] ?? ''), 'KQLCNT'))->groupBy('notify_no')->map(fn ($rows) => $rows->count());
        $detailCounts = $notifyNos->mapWithKeys(function (string $notifyNo) use ($records, $importCounts, $savedCounts): array {
            $record = $records->get($notifyNo);
            $snapshotApiCount = is_array($record?->verified_lots) ? count($record->verified_lots) : 0;

            return [$notifyNo => (int) ($importCounts[$notifyNo] ?? 0) + max((int) ($savedCounts[$notifyNo] ?? 0), $snapshotApiCount)];
        });
        $enrichedCounts = $notifyNos->mapWithKeys(function (string $notifyNo) use ($records, $importCounts, $enrichedSavedCounts): array {
            $record = $records->get($notifyNo);
            $snapshotApiCount = is_array($record?->verified_lots) ? count($record->verified_lots) : 0;

            return [$notifyNo => (int) ($importCounts[$notifyNo] ?? 0) + max((int) ($enrichedSavedCounts[$notifyNo] ?? 0), $snapshotApiCount)];
        });

        return view('Muasamcong::contractor-kqlcnt-recovery', [
            'contractorSearch' => $search, 'items' => $items, 'records' => $records, 'detailCounts' => $detailCounts,
            'enrichedCounts' => $enrichedCounts, 'batch' => $batch, 'fieldLabels' => $importer->fieldLabels(),
        ]);
    }

    private function supplementRows(ContractorSearch $search, string $notifyNo, ContractorAwardCatalogService $catalog, ?KqlcntRecord $record): array
    {
        $item = $this->assertNotifyScope($search, $notifyNo);
        $rows = $catalog->rows($search->contractor_code, [$notifyNo]);
        $contractNos = collect((array) $record?->contracts)->pluck('contractNo')->map(fn ($value) => trim((string) $value))->filter()->unique()->values();
        $contractNo = $contractNos->count() === 1 ? $contractNos->first() : null;
        $investor = $record?->investor_name ?: data_get($item->raw_payload, 'investorName') ?: data_get($item->raw_payload, 'procuringEntityName');

        if ($rows->isEmpty()) {
            return [[
                $notifyNo, $item->bid_name ?: data_get($item->raw_payload, 'bidName'), $search->contractor_code, $search->contractor_name,
                null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, $investor, $contractNo,
            ]];
        }

        return $rows->map(function (array $row) use ($search, $notifyNo, $item, $investor, $contractNo): array {
            return [
                $notifyNo,
                $item->bid_name ?: data_get($item->raw_payload, 'bidName'),
                $row['contractor_code'] ?: $search->contractor_code,
                $row['contractor_name'] ?: $search->contractor_name,
                $row['medicine_name'], $row['drug_group'], $row['active_ingredient'], $row['concentration'], $row['route'], $row['dosage_form'], $row['unit'],
                $row['medicine_code'], $row['lot_no'], $row['lot_name'], $row['quantity'], $row['price_plan'], $row['winning_price'], $row['amount'],
                $row['manufacturer'], $row['country'], $row['decision_no'], $row['decision_date'], $row['published_at'], $row['investor_name'] ?: $investor,
                $row['contract_no'] ?: $contractNo,
            ];
        })->all();
    }

    private function importHeadings(): array
    {
        return ['Mã TBMT', 'Tên gói thầu', 'Mã nhà thầu', 'Tên nhà thầu', 'Tên thuốc', 'Nhóm thuốc', 'Hoạt chất', 'Hàm lượng', 'Đường dùng', 'Dạng bào chế', 'ĐVT', 'Mã thuốc', 'Mã lô', 'Tên lô', 'Số lượng', 'Giá kế hoạch', 'Giá trúng thầu', 'Thành tiền', 'Nhà sản xuất', 'Nước SX', 'Số quyết định', 'Ngày quyết định', 'Ngày đăng KQLCNT', 'Chủ đầu tư', 'Số hợp đồng'];
    }

    private function assertNotifyScope(ContractorSearch $search, string $notifyNo)
    {
        $notifyNo = trim($notifyNo);
        $item = $search->items()->where('notify_no', $notifyNo)->first();
        abort_unless($item, 404);

        return $item;
    }

    private function assertBatchScope(ContractorSearch $search, KqlcntImportBatch $batch): void
    {
        abort_unless((int) $batch->contractor_search_id === (int) $search->id, 404);
    }
}
