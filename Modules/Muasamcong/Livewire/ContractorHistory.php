<?php

namespace Modules\Muasamcong\Livewire;

use Livewire\Component;
use Modules\Muasamcong\Models\ContractorBid;
use Modules\Muasamcong\Models\KqlcntRecord;
use Modules\Muasamcong\Services\ContractorHistoryService;
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
    public ?string $error = null;
    public ?string $notice = null;
    public int $reportedTotal = 0;

    public function searchCompany(MuaSamCongService $pricing): void
    {
        $this->reset(['companies', 'results', 'selected', 'detail', 'kqlcnt', 'error', 'notice', 'contractorCode', 'contractorName']);
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
        $this->error = null;
        $this->notice = null;
        $this->results = [];
        $this->selected = [];
        $this->detail = null;
        $this->kqlcnt = null;

        if ($this->contractorCode === '') {
            $this->error = 'Chưa chọn doanh nghiệp.';
            return;
        }

        if ($this->toDate && $this->toDate < $this->fromDate) {
            $this->error = 'Đến ngày phải lớn hơn hoặc bằng Từ ngày.';
            return;
        }

        try {
            $data = ($service ?? app(ContractorHistoryService::class))->search(
                $this->contractorCode,
                $this->fromDate,
                $this->toDate
            );
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

            $exists = ContractorBid::query()
                ->where('contractor_code', $this->contractorCode)
                ->where('notify_no', $notifyNo)
                ->exists();

            if ($exists) {
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
        $this->detail = collect($this->results)->firstWhere('notifyNo', $notifyNo);
        $this->kqlcnt = null;
    }

    public function showKqlcnt(string $notifyNo, KqlcntService $service): void
    {
        $this->error = null;
        $this->notice = null;
        $this->kqlcnt = null;

        if ($this->contractorCode === '') {
            $this->error = 'Chưa chọn doanh nghiệp để đối chiếu KQLCNT.';
            return;
        }

        try {
            $this->kqlcnt = $service->resolveByNotifyNo($notifyNo, $this->contractorCode);
        } catch (Throwable $e) {
            report($e);
            $this->error = $e->getMessage() ?: 'Không thể tải KQLCNT.';
        }
    }

    public function syncKqlcnt(): void
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

        $winner = collect($this->kqlcnt['contracts'] ?? [])
            ->flatMap(fn (array $contract): array => $contract['contractorPassListParsed'] ?? [])
            ->first(fn (mixed $item): bool => is_array($item)
                && trim((string) ($item['contractorCode'] ?? '')) === $this->contractorCode);

        KqlcntRecord::updateOrCreate(
            [
                'contractor_code' => $this->contractorCode,
                'notify_no' => $notifyNo,
            ],
            [
                'notify_id' => $this->kqlcnt['notify_id'] ?? null,
                'bid_id' => $this->kqlcnt['bid_id'] ?? null,
                'bid_name' => $this->kqlcnt['bid_name'] ?? null,
                'contractor_name' => is_array($winner) ? ($winner['contractorName'] ?? $this->contractorName) : $this->contractorName,
                'investor_code' => $this->kqlcnt['investor_code'] ?? null,
                'investor_name' => $this->kqlcnt['investor_name'] ?? null,
                'status' => $this->kqlcnt['status'] ?? null,
                'published' => (bool) ($this->kqlcnt['published'] ?? false),
                'contracts' => $this->kqlcnt['contracts'] ?? [],
                'verified_lots' => $this->kqlcnt['verified_lots'] ?? [],
                'tbmt_raw' => $this->kqlcnt['tbmt_raw'] ?? [],
                'contracts_raw' => $this->kqlcnt['contracts_raw'] ?? [],
                'synced_at' => now(),
            ]
        );

        $this->notice = 'Đã đồng bộ KQLCNT '.$notifyNo.' cho '.$this->contractorName.'.';
    }

    public function closeDetail(): void
    {
        $this->detail = null;
    }

    public function closeKqlcnt(): void
    {
        $this->kqlcnt = null;
    }

    public function render()
    {
        $synced = $this->contractorCode === '' ? [] : ContractorBid::query()
            ->where('contractor_code', $this->contractorCode)
            ->pluck('notify_no')->all();

        $kqlcntSynced = $this->contractorCode === '' ? [] : KqlcntRecord::query()
            ->where('contractor_code', $this->contractorCode)
            ->pluck('notify_no')->all();

        return view('Muasamcong::livewire.contractor-history', [
            'syncedNotifyNos' => array_fill_keys($synced, true),
            'syncedKqlcntNotifyNos' => array_fill_keys($kqlcntSynced, true),
        ]);
    }
}
