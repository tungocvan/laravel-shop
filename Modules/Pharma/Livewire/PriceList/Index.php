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
    public int $perPage = 10;
    public ?int $confirmingId = null;
    public ?string $confirmingAction = null;
    public ?string $errorMessage = null;

    protected $queryString = ['search' => ['except' => ''], 'type' => ['except' => 'all'], 'status' => ['except' => 'all'], 'perPage' => ['except' => 10]];

    public function mount(): void
    {
        $this->authorizePharmaView();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedType(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function confirm(int $id, string $action): void
    {
        $this->confirmingId = $id;
        $this->confirmingAction = $action;
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
        $list = PriceList::query()->findOrFail($this->confirmingId);

        try {
            if ($this->confirmingAction === 'activate') {
                $manager->activate($list, auth('admin')->id());
            } elseif ($this->confirmingAction === 'deactivate') {
                $manager->deactivate($list);
            } elseif ($this->confirmingAction === 'clone') {
                $manager->clone($list, ['name' => $list->name.' - Bản sao']);
            } elseif ($this->confirmingAction === 'delete') {
                $this->authorizePharmaEdit();
                $manager->deleteDraft($list);
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
        $query = PriceList::query()->with('partner')->withCount('items')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($inner) => $inner->where('code', 'like', '%'.$this->search.'%')->orWhere('name', 'like', '%'.$this->search.'%')))
            ->when($this->type !== 'all', fn ($q) => $q->where('type', $this->type))
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->latest('updated_at');

        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 10;
        $kpis = [
            'total' => PriceList::query()->count(),
            'active' => PriceList::query()->where('status', PriceList::STATUS_ACTIVE)->count(),
            'global' => PriceList::query()->where('type', PriceList::TYPE_GLOBAL)->count(),
            'customer' => PriceList::query()->where('type', PriceList::TYPE_CUSTOMER)->count(),
            'expiring' => PriceList::query()->where('status', PriceList::STATUS_ACTIVE)->whereBetween('effective_to', [today(), today()->addDays(30)])->count(),
        ];

        return view('Pharma::livewire.price-list.index', ['priceLists' => $query->paginate($perPage), 'kpis' => $kpis]);
    }
}
