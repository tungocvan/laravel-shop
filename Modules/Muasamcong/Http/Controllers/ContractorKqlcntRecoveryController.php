<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Muasamcong\Exports\KqlcntArraySheetExport;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Models\KqlcntImportBatch;
use Modules\Muasamcong\Models\KqlcntRecord;
use Modules\Muasamcong\Services\ContractorAwardCatalogService;
use Modules\Muasamcong\Services\ContractorAwardEnrichmentService;
use Modules\Muasamcong\Services\ContractorAwardPersistenceService;
use Modules\Muasamcong\Services\ContractorKqlcntExportService;
use Modules\Muasamcong\Services\KqlcntHistoricalImportService;
use RuntimeException;
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
        $batch = $importer->preview($batch, (array) $batch->mapping, $notifyNo);

        if ($batch->valid_rows > 0 && $batch->conflict_rows === 0 && $batch->error_rows === 0) {
            $importer->confirm($batch, false);

            return redirect()->route('muasamcong.contractors.kqlcnt-recovery', $contractorSearch)
                ->with('status', 'Đã bổ sung và lưu '.number_format($batch->valid_rows).' dòng KQLCNT cho '.$notifyNo.'.');
        }

        return redirect()->route('muasamcong.contractors.kqlcnt-recovery.batch', [$contractorSearch, $batch])
            ->with('status', 'File bổ sung đã được Preview nhưng chưa lưu vì có conflict hoặc lỗi. Kiểm tra trước khi xác nhận Import.');
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

    public function persist(ContractorSearch $contractorSearch, string $notifyNo, ContractorAwardPersistenceService $persistence): RedirectResponse
    {
        $this->assertNotifyScope($contractorSearch, $notifyNo);

        try {
            $result = $persistence->persist($contractorSearch, $notifyNo);
        } catch (RuntimeException $e) {
            return redirect()->route('muasamcong.contractors.kqlcnt-recovery', $contractorSearch)
                ->withErrors(['award_persistence' => $e->getMessage()]);
        }

        $message = 'Đã đồng bộ '.number_format($result['count']).' dòng trúng thầu của '.$notifyNo.' vào CSDL quản trị.';
        $message .= ' Mới '.number_format($result['created']).', cập nhật '.number_format($result['updated']).', không đổi '.number_format($result['unchanged']).'.';

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
        $importRows = KqlcntAwardItem::query()
            ->where('contractor_code', $search->contractor_code)
            ->whereIn('notify_no', $notifyNos)
            ->get(['notify_no', 'lot_no', 'medicine_code', 'medicine_name', 'active_ingredient', 'quantity', 'winning_price']);
        $warehouseRows = KqlcntAwardItem::query()
            ->where('contractor_code', $search->contractor_code)
            ->whereIn('notify_no', $notifyNos)
            ->where('is_active', true)
            ->whereNotNull('synced_from_catalog_at')
            ->get(['notify_no', 'synced_from_catalog_at']);
        $warehouseCounts = $warehouseRows->groupBy('notify_no')->map(fn (Collection $rows): int => $rows->count());
        $warehouseSyncedAt = $warehouseRows->groupBy('notify_no')->map(
            fn (Collection $rows) => $rows->sortByDesc('synced_from_catalog_at')->first()?->synced_from_catalog_at
        );
        $catalogRows = $catalog->rows($search->contractor_code, $notifyNos);

        $detailCounts = $notifyNos->mapWithKeys(function (string $notifyNo) use ($records, $importRows, $catalogRows): array {
            $keys = $this->logicalKeys($catalogRows->where('notify_no', $notifyNo));
            $keys = $keys->merge($this->logicalKeys($importRows->where('notify_no', $notifyNo)));
            $record = $records->get($notifyNo);
            $keys = $keys->merge($this->snapshotKeys((array) $record?->verified_lots));

            return [$notifyNo => $keys->filter()->unique()->count()];
        });

        $enrichedCounts = $notifyNos->mapWithKeys(function (string $notifyNo) use ($records, $importRows, $catalogRows): array {
            $enrichedCatalog = $catalogRows->where('notify_no', $notifyNo)->filter(fn (array $row): bool => str_contains((string) ($row['source'] ?? ''), 'SMART_PRICING') || str_contains((string) ($row['source'] ?? ''), 'KQLCNT'));
            $keys = $this->logicalKeys($enrichedCatalog);
            $keys = $keys->merge($this->logicalKeys($importRows->where('notify_no', $notifyNo)));
            $record = $records->get($notifyNo);
            $keys = $keys->merge($this->snapshotKeys((array) $record?->verified_lots));

            return [$notifyNo => $keys->filter()->unique()->count()];
        });

        return view('Muasamcong::contractor-kqlcnt-recovery', [
            'contractorSearch' => $search,
            'items' => $items,
            'records' => $records,
            'detailCounts' => $detailCounts,
            'enrichedCounts' => $enrichedCounts,
            'warehouseCounts' => $warehouseCounts,
            'warehouseSyncedAt' => $warehouseSyncedAt,
            'batch' => $batch,
            'fieldLabels' => $importer->fieldLabels(),
        ]);
    }

    private function logicalKeys(Collection $rows): Collection
    {
        return $rows->values()->map(fn ($row) => $this->logicalKey([
            'lot_no' => is_array($row) ? ($row['lot_no'] ?? null) : $row->lot_no,
            'medicine_code' => is_array($row) ? ($row['medicine_code'] ?? null) : $row->medicine_code,
            'medicine_name' => is_array($row) ? ($row['medicine_name'] ?? null) : $row->medicine_name,
            'active_ingredient' => is_array($row) ? ($row['active_ingredient'] ?? null) : $row->active_ingredient,
            'concentration' => is_array($row) ? ($row['concentration'] ?? null) : ($row->concentration ?? null),
        ]));
    }

    private function snapshotKeys(array $rows): Collection
    {
        return collect($rows)->values()->map(function ($row): ?string {
            $row = is_array($row) ? $row : [];

            return $this->logicalKey([
                'lot_no' => $row['lot_no'] ?? $row['lotNo'] ?? null,
                'medicine_code' => $row['medicine_code'] ?? $row['medicineCode'] ?? $row['maThuoc'] ?? null,
                'medicine_name' => $row['medicine_name'] ?? $row['medicineName'] ?? $row['tenThuoc'] ?? null,
                'active_ingredient' => $row['active_ingredient'] ?? $row['tenHoatChat'] ?? null,
                'concentration' => $row['concentration'] ?? $row['nongDo'] ?? null,
            ]);
        });
    }

    private function logicalKey(array $row): ?string
    {
        $lotNo = trim((string) ($row['lot_no'] ?? ''));
        if ($lotNo !== '') {
            return 'lot:'.mb_strtoupper($lotNo);
        }

        $medicineCode = trim((string) ($row['medicine_code'] ?? ''));
        if ($medicineCode !== '') {
            return 'medicine:'.mb_strtoupper($medicineCode);
        }

        $parts = collect(['medicine_name', 'active_ingredient', 'concentration'])
            ->map(fn (string $field): string => mb_strtolower(trim((string) ($row[$field] ?? ''))))
            ->filter();

        return $parts->isEmpty() ? null : 'medicine:'.hash('sha256', $parts->implode('|'));
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
                null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
                $investor, $contractNo,
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
                $row['manufacturer'], $row['country'], $row['packaging_spec'] ?? null, $row['shelf_life_months'] ?? null, $row['registration_or_import_license'] ?? null,
                $row['decision_no'], $row['decision_date'], $row['published_at'], $row['investor_name'] ?: $investor,
                $row['contract_no'] ?: $contractNo,
            ];
        })->all();
    }

    private function importHeadings(): array
    {
        return ['Mã TBMT', 'Tên gói thầu', 'Mã nhà thầu', 'Tên nhà thầu', 'Tên thuốc', 'Nhóm thuốc', 'Hoạt chất', 'Hàm lượng', 'Đường dùng', 'Dạng bào chế', 'ĐVT', 'Mã thuốc', 'Mã lô', 'Tên lô', 'Số lượng', 'Giá kế hoạch', 'Giá trúng thầu', 'Thành tiền', 'Nhà sản xuất', 'Nước SX', 'Quy cách', 'Hạn dùng (tháng)', 'GĐKLH hoặc GPNK', 'Số quyết định', 'Ngày quyết định', 'Ngày đăng KQLCNT', 'Chủ đầu tư', 'Số hợp đồng'];
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
