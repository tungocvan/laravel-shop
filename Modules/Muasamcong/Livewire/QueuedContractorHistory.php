<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Modules\Muasamcong\Jobs\FetchContractorHistoryJob;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\ContractorSearchJob;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Modules\Muasamcong\Services\ContractorSearchArchiveService;
use Throwable;

class QueuedContractorHistory extends ContractorHistory
{
    public ?int $queueJobId = null;

    public bool $showQueueModal = false;

    public string $queueStatus = '';

    public string $queueMessage = '';

    public int $queueProgress = 0;

    public function selectCompany(string $code): void
    {
        $company = collect($this->companies)->firstWhere('code', $code);
        if (! $company) {
            return;
        }

        $this->contractorCode = (string) $company['code'];
        $this->contractorName = (string) $company['name'];

        if (($company['source'] ?? '') === 'server' && ! empty($company['search_id'])) {
            $search = ContractorSearch::query()->find((int) $company['search_id']);
            if ($search) {
                $this->loadArchive($search);

                return;
            }
        }

        $this->dispatchFreshSearch();
    }

    public function loadHistory(?ContractorHistoryService $service = null): void
    {
        $this->dispatchFreshSearch();
    }

    public function searchFresh(): void
    {
        $this->dispatchFreshSearch();
    }

    public function pollQueue(): void
    {
        if (! $this->queueJobId) {
            return;
        }

        $job = ContractorSearchJob::query()->find($this->queueJobId);
        if (! $job) {
            $this->showQueueModal = false;
            $this->queueJobId = null;

            return;
        }

        $this->queueStatus = $job->status;
        $this->queueProgress = (int) $job->progress;
        $this->queueMessage = (string) ($job->status_message ?: 'Hệ thống đang thực thi...');

        if ($job->status === 'completed' && $job->contractor_search_id) {
            $search = ContractorSearch::query()->find($job->contractor_search_id);
            if ($search) {
                $this->loadArchive($search);
            }

            $this->showQueueModal = false;
            $this->notice = 'Tra cứu hoàn tất và đã lưu dữ liệu trên server.';
        }

        if ($job->status === 'failed') {
            $this->showQueueModal = false;
            $this->error = 'Tra cứu nhà thầu không thành công. Vui lòng kiểm tra queue log hoặc Session Mua sắm công.';
        }
    }

    public function render(): View
    {
        $parent = parent::render();

        return view('Muasamcong::livewire.queued-contractor-history', $parent->getData());
    }

    private function dispatchFreshSearch(): void
    {
        $this->error = null;
        $this->notice = null;

        if ($this->contractorCode === '') {
            $this->error = 'Chưa chọn doanh nghiệp.';

            return;
        }

        if ($this->toDate && $this->toDate < $this->fromDate) {
            $this->error = 'Đến ngày phải lớn hơn hoặc bằng Từ ngày.';

            return;
        }

        try {
            app(ContractorHistoryService::class)->testSession();
        } catch (Throwable) {
            $this->showSessionExpiredModal = true;

            return;
        }

        $running = ContractorSearchJob::query()
            ->where('contractor_code', $this->contractorCode)
            ->whereIn('status', ['queued', 'running', 'saving'])
            ->latest('id')
            ->first();

        if ($running) {
            $job = $running;
        } else {
            $job = ContractorSearchJob::query()->create([
                'contractor_code' => $this->contractorCode,
                'contractor_name' => $this->contractorName,
                'from_date' => $this->fromDate,
                'to_date' => $this->toDate,
                'status' => 'queued',
                'progress' => 0,
                'status_message' => 'Đã đưa yêu cầu vào hàng đợi. Đang chờ worker xử lý...',
                'requested_by' => Auth::guard('admin')->id(),
            ]);

            FetchContractorHistoryJob::dispatch($job->id);
        }

        $this->queueJobId = $job->id;
        $this->queueStatus = $job->status;
        $this->queueProgress = (int) $job->progress;
        $this->queueMessage = (string) $job->status_message;
        $this->showQueueModal = true;
    }

    private function loadArchive(ContractorSearch $search): void
    {
        $archive = app(ContractorSearchArchiveService::class);
        $this->historySearchId = $search->id;
        $this->contractorCode = $search->contractor_code;
        $this->contractorName = $search->contractor_name ?: $search->contractor_code;
        $this->companyKeyword = $this->contractorName;
        $this->reportedTotal = (int) $search->reported_total;
        $this->historyPage = 1;

        $page = $archive->page($search, $this->historyPage, $this->historyPerPage);
        $this->results = $page['items'];
        $this->historyTotalPages = $page['total_pages'];
    }
}
