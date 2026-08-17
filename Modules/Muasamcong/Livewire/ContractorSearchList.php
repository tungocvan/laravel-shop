<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Muasamcong\Jobs\FetchContractorHistoryJob;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\ContractorSearchItem;
use Modules\Muasamcong\Models\ContractorSearchJob;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Throwable;

class ContractorSearchList extends Component
{
    use WithPagination;

    public string $keyword = '';

    public bool $showSessionExpiredModal = false;

    public ?int $confirmDeleteId = null;

    protected $queryString = [
        'keyword' => ['as' => 'q', 'except' => ''],
    ];

    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    public function refreshSearch(int $searchId): void
    {
        $search = ContractorSearch::query()->find($searchId);
        if (! $search) {
            return;
        }

        try {
            app(ContractorHistoryService::class)->testSession();
        } catch (Throwable) {
            $this->showSessionExpiredModal = true;

            return;
        }

        $running = ContractorSearchJob::query()
            ->where('contractor_code', $search->contractor_code)
            ->whereIn('status', ['queued', 'running', 'saving'])
            ->latest('id')
            ->first();

        if (! $running) {
            $running = ContractorSearchJob::query()->create([
                'contractor_code' => $search->contractor_code,
                'contractor_name' => $search->contractor_name,
                'from_date' => $search->from_date,
                'to_date' => $search->to_date,
                'status' => 'queued',
                'progress' => 0,
                'status_message' => 'Đã đưa yêu cầu cập nhật vào hàng đợi...',
                'requested_by' => Auth::guard('admin')->id(),
            ]);

            FetchContractorHistoryJob::dispatch($running->id);
        }
    }

    public function askDelete(int $searchId): void
    {
        $this->confirmDeleteId = $searchId;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function deleteSearch(): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        $search = ContractorSearch::query()->find($this->confirmDeleteId);
        if ($search) {
            ContractorSearchItem::query()->where('contractor_search_id', $search->id)->delete();
            $search->delete();
        }

        $this->confirmDeleteId = null;
        $this->resetPage();
    }

    public function closeSessionExpiredModal(): void
    {
        $this->showSessionExpiredModal = false;
    }

    public function render(): View
    {
        $keyword = trim($this->keyword);
        $searches = ContractorSearch::query()
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $normalized = mb_strtolower($keyword);
                $query->where(function ($nested) use ($keyword, $normalized): void {
                    $nested->where('contractor_name', 'like', "%{$keyword}%")
                        ->orWhere('contractor_code', 'like', "%{$normalized}%")
                        ->orWhere('tax_code', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('last_searched_at')
            ->paginate(20);

        $codes = $searches->getCollection()->pluck('contractor_code')->filter()->values();
        $latestJobs = ContractorSearchJob::query()
            ->whereIn('contractor_code', $codes)
            ->orderByDesc('id')
            ->get()
            ->unique('contractor_code')
            ->keyBy('contractor_code');

        $cataloguesByCode = ContractorManualLot::query()
            ->whereIn('contractor_code', $codes)
            ->selectRaw('contractor_code, notify_no, COUNT(*) as lot_count, SUM(COALESCE(plan_amount, 0)) as plan_amount')
            ->groupBy('contractor_code', 'notify_no')
            ->orderByDesc('notify_no')
            ->get()
            ->groupBy('contractor_code');

        $hasRunningJobs = $latestJobs->contains(fn (ContractorSearchJob $job): bool => in_array($job->status, ['queued', 'running', 'saving'], true));

        return view('Muasamcong::livewire.contractor-search-list', compact('searches', 'latestJobs', 'cataloguesByCode', 'hasRunningJobs'));
    }
}
