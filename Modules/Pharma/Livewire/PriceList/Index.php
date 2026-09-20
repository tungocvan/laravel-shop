<?php

namespace Modules\Pharma\Livewire\PriceList;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Services\PriceListManager;
use Throwable;

class Index extends Component
{
    use AuthorizesPharmaActions;
    use WithPagination;

    public string $search = '';
    public string $type = 'all';
    public string $status = 'all';
    public string $managerUserId = 'all';
    public string $effectiveFrom = '';
    public string $effectiveTo = '';
    public string $appliedEffectiveFrom = '';
    public string $appliedEffectiveTo = '';
    public string $sortField = 'updated_at';
    public string $sortDirection = 'desc';
    public int $perPage = 10;
    public ?int $confirmingId = null;
    public ?string $confirmingAction = null;
    public ?string $errorMessage = null;
    public array $selectedIds = [];

    protected $queryString = ['search' => ['except' => ''], 'type' => ['except' => 'all'], 'status' => ['except' => 'all'], 'managerUserId' => ['except' => 'all'], 'appliedEffectiveFrom' => ['except' => ''], 'appliedEffectiveTo' => ['except' => ''], 'sortField' => ['except' => 'updated_at'], 'sortDirection' => ['except' => 'desc'], 'perPage' => ['except' => 10]];

    public function mount(): void
    {
        $this->authorizePharmaView();
        $today = now()->toDateString();
        $this->effectiveFrom = $this->appliedEffectiveFrom !== '' ? $this->appliedEffectiveFrom : $today;
        $this->effectiveTo = $this->appliedEffectiveTo !== '' ? $this->appliedEffectiveTo : $today;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedManagerUserId(): void
    {
        $this->resetPage();
    }

    public function applyEffectiveDates(): void
    {
        $this->appliedEffectiveFrom = $this->effectiveFrom;
        $this->appliedEffectiveTo = $this->effectiveTo;
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $today = now()->toDateString();
        $this->search = '';
        $this->type = 'all';
        $this->status = 'all';
        $this->managerUserId = 'all';
        $this->effectiveFrom = $today;
        $this->effectiveTo = $today;
        $this->appliedEffectiveFrom = '';
        $this->appliedEffectiveTo = '';
        $this->sortField = 'updated_at';
        $this->sortDirection = 'desc';
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['effective_from', 'effective_to'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function confirm(int $id, string $action): void
    {
        $this->confirmingId = $id;
        $this->confirmingAction = $action;
        $this->errorMessage = null;
    }

    public function confirmBulkDelete(): void
    {
        $this->authorizePharmaEdit();

        if ($this->selectedIds === []) {
            $this->errorMessage = 'Vui lòng chọn ít nhất một bảng giá để xóa.';

            return;
        }

        $this->confirmingId = null;
        $this->confirmingAction = 'bulk-delete';
        $this->errorMessage = null;
    }

    public function cancelConfirm(): void
    {
        $this->confirmingId = null;
        $this->confirmingAction = null;
        $this->errorMessage = null;
    }

    public function executeConfirmed(PriceListManager $manager): void
    {
        try {
            if ($this->confirmingAction === 'bulk-delete') {
                $this->authorizePharmaEdit();
                $deleted = $manager->deleteSelected($this->selectedIds);
                $this->selectedIds = [];
                $this->cancelConfirm();
                session()->flash('success', "Đã xóa {$deleted} bảng giá.");

                return;
            }

            $list = PriceList::query()->findOrFail($this->confirmingId);

            if ($this->confirmingAction === 'activate') {
                $manager->activate($list, auth('admin')->id());
            } elseif ($this->confirmingAction === 'deactivate') {
                $manager->deactivate($list);
            } elseif ($this->confirmingAction === 'clone') {
                $manager->clone($list, ['name' => $list->name.' - Bản sao']);
            } elseif ($this->confirmingAction === 'delete') {
                $this->authorizePharmaEdit();
                $manager->deleteRemovable($list);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = $exception->getMessage();

            return;
        }

        $message = $this->confirmingAction === 'delete' ? 'Đã xóa bảng giá Draft.' : 'Thao tác bảng giá đã hoàn tất.';
        $this->cancelConfirm();
        session()->flash('success', $message);
    }

    public function render()
    {
        $query = PriceList::query()->with(['partner', 'officialFacility', 'manager'])->withCount('items')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($inner) => $inner->where('code', 'like', '%'.$this->search.'%')->orWhere('name', 'like', '%'.$this->search.'%')))
            ->when($this->type !== 'all', fn ($q) => $q->where('type', $this->type))
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->managerUserId !== 'all', fn ($q) => $q->where('manager_user_id', (int) $this->managerUserId))
            ->when($this->appliedEffectiveFrom !== '', fn ($q) => $q->where(fn ($dates) => $dates->whereNull('effective_to')->orWhereDate('effective_to', '>=', $this->appliedEffectiveFrom)))
            ->when($this->appliedEffectiveTo !== '', fn ($q) => $q->where(fn ($dates) => $dates->whereNull('effective_from')->orWhereDate('effective_from', '<=', $this->appliedEffectiveTo)))
            ->orderBy(in_array($this->sortField, ['effective_from', 'effective_to'], true) ? $this->sortField : 'updated_at', $this->sortDirection === 'asc' ? 'asc' : 'desc');

        $hasActiveFilters = $this->search !== '' || $this->type !== 'all' || $this->status !== 'all' || $this->managerUserId !== 'all' || $this->appliedEffectiveFrom !== '' || $this->appliedEffectiveTo !== '' || $this->sortField !== 'updated_at';

        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 10;
        $kpis = [
            'total' => PriceList::query()->count(),
            'active' => PriceList::query()->where('status', PriceList::STATUS_ACTIVE)->count(),
            'global' => PriceList::query()->where('type', PriceList::TYPE_GLOBAL)->count(),
            'customer' => PriceList::query()->where('type', PriceList::TYPE_CUSTOMER)->count(),
            'expiring' => PriceList::query()->where('status', PriceList::STATUS_ACTIVE)->whereBetween('effective_to', [today(), today()->addDays(30)])->count(),
        ];

        $managers = \App\Models\User::query()
            ->whereIn('id', PriceList::query()->whereNotNull('manager_user_id')->select('manager_user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('Pharma::livewire.price-list.index', ['priceLists' => $query->paginate($perPage), 'kpis' => $kpis, 'managers' => $managers, 'hasActiveFilters' => $hasActiveFilters]);
    }
}
