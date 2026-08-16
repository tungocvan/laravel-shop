<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Modules\Muasamcong\Models\ContractorBid;
use Modules\Muasamcong\Models\KqlcntRecord;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Modules\Muasamcong\Services\HsmtDetailService;
use Modules\Muasamcong\Services\HsmtSnapshotService;
use Modules\Muasamcong\Services\KqlcntService;
use Modules\Muasamcong\Services\MuaSamCongService;
use Throwable;

class ContractorHistory extends Component
{
    public string $companyKeyword = '';

    public string $contractorCode = '';

    public string $contractorName = '';

    public string $fromDate = '2021-01-01';

    public ?string $toDate = null;

    public array $companies = [];

    public array $results = [];

    public array $selected = [];

    public ?array $detail = null;

    public ?array $kqlcnt = null;

    public ?array $hsmt = null;

    public ?string $hsmtCacheKey = null;

    public string $hsmtSearch = '';

    public string $hsmtGroup = '';

    public int $hsmtPage = 1;

    public int $hsmtPerPage = 20;

    public ?string $error = null;

    public ?string $notice = null;

    public int $reportedTotal = 0;

    public function searchCompany(MuaSamCongService $pricing): void
    {
        $this->clearHsmtCache();
        $this->reset(['companies', 'results', 'selected', 'detail', 'kqlcnt', 'hsmt', 'hsmtCacheKey', 'error', 'notice', 'contractorCode', 'contractorName']);
        $keyword = trim($this->companyKeyword);

        if (mb_strlen($keyword) < 3) {
            $this->error = 'Nhập ít nhất 3 ký tự tên doanh nghiệp.';

            return;
        }

        $response = $pricing->searchPricing($keyword);
        if (! ($response['success'] ?? false)) {
            $this->error = $response['message'] ?? 'Không thể tìm doanh nghiệp.';

            return;
        }

        $companies = [];
        foreach (($response['data']['items'] ?? []) as $item) {
            foreach ((array) ($item['winningCode'] ?? []) as $index => $code) {
                $name = (array) ($item['winningName'] ?? []);
                $label = trim((string) ($name[$index] ?? $name[0] ?? ''));
                $code = trim((string) $code);
                if ($code !== '' && $label !== '' && str_contains(mb_strtoupper($label), mb_strtoupper($keyword))) {
                    $companies[$code] = ['code' => $code, 'name' => $label];
                }
            }
        }

        $this->companies = array_values($companies);
        if (count($this->companies) === 1) {
            $this->selectCompany($this->companies[0]['code']);
        } elseif ($this->companies === []) {
            $this->error = 'Không xác định được mã nhà thầu từ kết quả Smart Pricing.';
        }
    }

    public function selectCompany(string $code): void
    {
        $company = collect($this->companies)->firstWhere('code', $code);
        if (! $company) {
            return;
        }

        $this->contractorCode = $company['code'];
        $this->contractorName = $company['name'];
        $this->loadHistory();
    }

    public function loadHistory(?ContractorHistoryService $service = null): void
    {
        $this->clearHsmtCache();
        $this->error = null;
        $this->notice = null;
        $this->results = [];
        $this->selected = [];
        $this->detail = null;
        $this->kqlcnt = null;
        $this->hsmt = null;
        $this->hsmtCacheKey = null;

        if ($this->contractorCode === '') {
            $this->error = 'Chưa chọn doanh nghiệp.';

            return;
        }

        if ($this->toDate && $this->toDate < $this->fromDate) {
            $this->error = 'Đến ngày phải lớn hơn hoặc bằng Từ ngày.';

            return;
        }

        try {
            $data = ($service ?? app(ContractorHistoryService::class))->search($this->contractorCode, $this->fromDate, $this->toDate);
            $this->results = $data['items'];
            $this->reportedTotal = (int) $data['reported_total'];
        } catch (Throwable $e) {
            report($e);
            $this->error = 'Không thể tải lịch sử nhà thầu. Hãy kiểm tra session Mua sắm công.';
        }
    }

    public function selectAll(): void
    {
        $this->selected = collect($this->results)->pluck('notifyNo')->filter()->values()->all();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function syncSelected(): void
    {
        $selected = array_fill_keys($this->selected, true);
        $synced = 0;
        $skipped = 0;

        foreach ($this->results as $row) {
            $notifyNo = (string) ($row['notifyNo'] ?? '');
            if ($notifyNo === '' || ! isset($selected[$notifyNo])) {
                continue;
            }

            if (ContractorBid::query()->where('contractor_code', $this->contractorCode)->where('notify_no', $notifyNo)->exists()) {
                $skipped++;

                continue;
            }

            ContractorBid::create([
                'source_id' => $row['id'] ?? null,
                'notify_no' => $notifyNo,
                'bid_name' => $row['bidName'] ?? null,
                'contractor_code' => $row['contractorCode'] ?? $this->contractorCode,
                'procuring_entity_code' => $row['procuringEntityCode'] ?? null,
                'created_date' => $row['createdDate'] ?? null,
                'date_year' => $row['dateYear'] ?? null,
                'date_quarter' => $row['dateQuarter'] ?? null,
                'date_month' => $row['dateMonth'] ?? null,
                'participation_status' => 'joined',
                'award_status' => 'unknown',
                'raw_payload' => $row,
                'synced_at' => now(),
            ]);
            $synced++;
        }

        $this->selected = [];
        $this->notice = "Đã đồng bộ {$synced} gói; bỏ qua {$skipped} gói đã tồn tại.";
    }

    public function showDetail(string $notifyNo): void
    {
        $this->clearHsmtCache();
        $this->detail = collect($this->results)->firstWhere('notifyNo', $notifyNo);
        $this->kqlcnt = null;
        $this->hsmt = null;
        $this->hsmtCacheKey = null;
    }

    public function showKqlcnt(string $notifyNo, KqlcntService $service): void
    {
        $this->clearHsmtCache();
        $this->error = null;
        $this->notice = null;
        $this->kqlcnt = null;
        $this->hsmt = null;
        $this->hsmtCacheKey = null;

        if ($this->contractorCode === '') {
            $this->error = 'Chưa chọn doanh nghiệp để đối chiếu KQLCNT.';

            return;
        }

        try {
            $stored = KqlcntRecord::query()
                ->where('contractor_code', $this->contractorCode)
                ->where('notify_no', $notifyNo)
                ->first();

            if ($stored) {
                $this->kqlcnt = $service->normalizeStored($stored->toArray());

                return;
            }

            $this->kqlcnt = $service->resolveByNotifyNo($notifyNo, $this->contractorCode);
            $this->kqlcnt['source'] = 'api';
        } catch (Throwable $e) {
            report($e);
            $this->error = $e->getMessage() ?: 'Không thể tải KQLCNT.';
        }
    }

    public function loadHsmt(HsmtDetailService $service, HsmtSnapshotService $snapshots): void
    {
        if (! is_array($this->kqlcnt) || empty($this->kqlcnt['notify_id'])) {
            $this->error = 'Chưa có notifyId để tải HSMT.';

            return;
        }

        $notifyNo = trim((string) ($this->kqlcnt['notify_no'] ?? ''));
        if ($notifyNo === '') {
            $this->error = 'KQLCNT không có mã TBMT hợp lệ.';

            return;
        }

        $this->error = null;
        $this->clearHsmtCache();

        try {
            $record = KqlcntRecord::query()
                ->where('contractor_code', $this->contractorCode)
                ->where('notify_no', $notifyNo)
                ->first();

            $snapshot = $record ? $snapshots->load($record->hsmt_json_path) : null;
            $snapshot ??= $snapshots->loadForNotifyNo($notifyNo);

            if ($snapshot) {
                $this->hydrateHsmtSnapshot($snapshot);

                return;
            }

            $data = $service->fetch((string) $this->kqlcnt['notify_id'], 'LDT');
            $metadata = $snapshots->store($notifyNo, $data);
            $this->persistHsmtMetadata($record, $metadata);
            $this->hydrateHsmtSnapshot($data);
            $this->notice = 'Đã tải và lưu snapshot HSMT trên server.';
        } catch (Throwable $e) {
            report($e);
            $this->error = $e->getMessage() ?: 'Không thể tải danh mục HSMT.';
        }
    }

    public function syncHsmt(HsmtDetailService $service, HsmtSnapshotService $snapshots): void
    {
        if (! is_array($this->kqlcnt) || empty($this->kqlcnt['notify_id'])) {
            $this->error = 'Chưa có notifyId để đồng bộ lại HSMT.';

            return;
        }

        $notifyNo = trim((string) ($this->kqlcnt['notify_no'] ?? ''));
        if ($notifyNo === '') {
            $this->error = 'KQLCNT không có mã TBMT hợp lệ.';

            return;
        }

        $this->error = null;
        $this->clearHsmtCache();

        try {
            $data = $service->fetch((string) $this->kqlcnt['notify_id'], 'LDT');
            $metadata = $snapshots->store($notifyNo, $data);
            $record = KqlcntRecord::query()
                ->where('contractor_code', $this->contractorCode)
                ->where('notify_no', $notifyNo)
                ->first();
            $this->persistHsmtMetadata($record, $metadata);
            $this->hydrateHsmtSnapshot($data);
            $this->notice = 'Đã đồng bộ lại snapshot HSMT từ Cổng Mua sắm công.';
        } catch (Throwable $e) {
            report($e);
            $this->error = $e->getMessage() ?: 'Không thể đồng bộ lại danh mục HSMT.';
        }
    }

    public function updatedHsmtSearch(): void
    {
        $this->hsmtPage = 1;
    }

    public function updatedHsmtGroup(): void
    {
        $this->hsmtPage = 1;
    }

    public function hsmtPreviousPage(): void
    {
        $this->hsmtPage = max(1, $this->hsmtPage - 1);
    }

    public function hsmtNextPage(): void
    {
        if ($this->hsmtPage < $this->hsmtTotalPages()) {
            $this->hsmtPage++;
        }
    }

    private function hsmtItems(): array
    {
        if (! $this->hsmtCacheKey) {
            return [];
        }

        $items = Cache::get($this->hsmtCacheKey, []);

        return is_array($items) ? $items : [];
    }

    private function filteredHsmtItems(): array
    {
        $items = $this->hsmtItems();
        $search = mb_strtoupper(trim($this->hsmtSearch));
        $group = trim($this->hsmtGroup);

        return array_values(array_filter($items, function (array $row) use ($search, $group): bool {
            if ($group !== '' && ($row['medicine_group'] ?? '') !== $group) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            $haystack = mb_strtoupper(implode(' ', array_filter([
                $row['lot_no'] ?? null,
                $row['lot_name'] ?? null,
                $row['medicine_name'] ?? null,
                $row['active_ingredient'] ?? null,
                $row['medicine_code'] ?? null,
            ])));

            return str_contains($haystack, $search);
        }));
    }

    private function hsmtTotalPages(): int
    {
        return max(1, (int) ceil(count($this->filteredHsmtItems()) / $this->hsmtPerPage));
    }

    private function clearHsmtCache(): void
    {
        if ($this->hsmtCacheKey) {
            Cache::forget($this->hsmtCacheKey);
        }
    }

    private function hydrateHsmtSnapshot(array $data): void
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        unset($data['items']);

        $this->hsmtCacheKey = 'muasamcong:hsmt:'.hash('sha256', implode('|', [
            (string) ($this->kqlcnt['notify_id'] ?? ''),
            $this->contractorCode,
            (string) auth('admin')->id(),
        ]));
        Cache::put($this->hsmtCacheKey, $items, now()->addMinutes(30));

        $this->hsmt = $data;
        $this->hsmtPage = 1;
        $this->hsmtSearch = '';
        $this->hsmtGroup = '';
    }

    private function persistHsmtMetadata(?KqlcntRecord $record, array $metadata): void
    {
        if (! $record) {
            return;
        }

        $record->update([
            'hsmt_json_path' => $metadata['json_path'] ?? null,
            'hsmt_excel_path' => $metadata['excel_path'] ?? null,
            'hsmt_total_items' => $metadata['total'] ?? null,
            'hsmt_checksum' => $metadata['checksum'] ?? null,
            'hsmt_synced_at' => now(),
        ]);
    }

    public function syncKqlcnt(KqlcntService $service): void
    {
        if (! is_array($this->kqlcnt) || $this->contractorCode === '') {
            $this->error = 'Chưa có dữ liệu KQLCNT để đồng bộ.';

            return;
        }

        $notifyNo = trim((string) ($this->kqlcnt['notify_no'] ?? ''));
        if ($notifyNo === '') {
            $this->error = 'KQLCNT không có mã TBMT hợp lệ.';

            return;
        }

        try {
            if (($this->kqlcnt['source'] ?? 'api') === 'server') {
                $notifyId = trim((string) ($this->kqlcnt['notify_id'] ?? ''));
                $this->kqlcnt = $notifyId !== ''
                    ? $service->resolve($notifyId, $notifyNo, $this->contractorCode)
                    : $service->resolveByNotifyNo($notifyNo, $this->contractorCode);
                $this->kqlcnt['source'] = 'api';
            }

            $winner = collect($this->kqlcnt['all_winners'] ?? [])
                ->first(fn (mixed $item): bool => is_array($item)
                    && trim((string) ($item['contractorCode'] ?? '')) === $this->contractorCode);

            $record = KqlcntRecord::updateOrCreate(
                ['contractor_code' => $this->contractorCode, 'notify_no' => $notifyNo],
                [
                    'notify_id' => $this->kqlcnt['notify_id'] ?? null,
                    'bid_id' => $this->kqlcnt['bid_id'] ?? null,
                    'bid_name' => $this->kqlcnt['bid_name'] ?? null,
                    'contractor_name' => is_array($winner) ? ($winner['contractorName'] ?? $this->contractorName) : $this->contractorName,
                    'investor_code' => $this->kqlcnt['investor_code'] ?? null,
                    'investor_name' => $this->kqlcnt['investor_name'] ?? null,
                    'status' => $this->kqlcnt['status'] ?? null,
                    'published' => (bool) ($this->kqlcnt['published'] ?? false),
                    'current_contractor_won' => (bool) ($this->kqlcnt['current_contractor_won'] ?? false),
                    'contracts' => $this->kqlcnt['contracts'] ?? [],
                    'all_winners' => $this->kqlcnt['all_winners'] ?? [],
                    'verified_lots' => $this->kqlcnt['verified_lots'] ?? [],
                    'tbmt_raw' => $this->kqlcnt['tbmt_raw'] ?? [],
                    'contracts_raw' => $this->kqlcnt['contracts_raw'] ?? [],
                    'synced_at' => now(),
                ]
            );

            $this->kqlcnt = $service->normalizeStored($record->fresh()->toArray());
            $this->notice = 'Đã đồng bộ KQLCNT '.$notifyNo.' vào server.';
        } catch (Throwable $e) {
            report($e);
            $this->error = $e->getMessage() ?: 'Không thể đồng bộ KQLCNT.';
        }
    }

    public function closeDetail(): void
    {
        $this->detail = null;
    }

    public function closeKqlcnt(): void
    {
        $this->clearHsmtCache();
        $this->kqlcnt = null;
        $this->hsmt = null;
        $this->hsmtCacheKey = null;
    }

    public function render()
    {
        $synced = $this->contractorCode === '' ? [] : ContractorBid::query()->where('contractor_code', $this->contractorCode)->pluck('notify_no')->all();
        $kqlcntSynced = $this->contractorCode === '' ? [] : KqlcntRecord::query()->where('contractor_code', $this->contractorCode)->pluck('notify_no')->all();
        $allHsmtItems = $this->hsmtItems();
        $filteredHsmt = $this->filteredHsmtItems();
        $totalPages = $this->hsmtTotalPages();
        $this->hsmtPage = min($this->hsmtPage, $totalPages);
        $pageItems = array_slice($filteredHsmt, ($this->hsmtPage - 1) * $this->hsmtPerPage, $this->hsmtPerPage);
        $groups = collect($allHsmtItems)->pluck('medicine_group')->filter()->unique()->sort()->values()->all();

        return view('Muasamcong::livewire.contractor-history', [
            'syncedNotifyNos' => array_fill_keys($synced, true),
            'syncedKqlcntNotifyNos' => array_fill_keys($kqlcntSynced, true),
            'hsmtItems' => $pageItems,
            'hsmtFilteredTotal' => count($filteredHsmt),
            'hsmtTotalPages' => $totalPages,
            'hsmtGroups' => $groups,
        ]);
    }
}
