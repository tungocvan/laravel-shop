<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Livewire\Component;
use Modules\Pharma\Models\DrugBidAward;

class ProductWorkspace extends Component
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public int $awardId;
    public string $search = '';
    public int $perPage = 10;
    public int $page = 1;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'page' => ['except' => 1],
    ];

    public function mount(int $awardId): void
    {
        abort_unless(auth('admin')->user()?->can('view_pharma_allocations'), 403);
        DrugBidAward::query()->findOrFail($awardId);
        $this->awardId = $awardId;
        $this->perPage = $this->normalizePerPage($this->perPage);
    }

    public function updatedSearch(): void { $this->page = 1; }
    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizePerPage($value);
        $this->page = 1;
    }
    public function gotoPage(int $page): void { $this->page = max(1, $page); }

    public function render()
    {
        $result = DrugBidAward::query()->findOrFail($this->awardId);
        $query = DrugBidAward::query()
            ->with(['medicine', 'allocations' => fn ($query) => $query->where('status', 'active')])
            ->when($result->bidding_notice_code, fn ($query, $code) => $query->where('bidding_notice_code', $code), fn ($query) => $query->whereKey($result->id))
            ->when($this->search, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('medicine_name', 'like', "%{$search}%")
                ->orWhere('active_ingredient', 'like', "%{$search}%")
                ->orWhere('lot_name', 'like', "%{$search}%")
                ->orWhere('registration_or_import_license', 'like', "%{$search}%")))
            ->orderByRaw("CASE WHEN lot_no IS NULL OR lot_no = '' THEN 1 ELSE 0 END")
            ->orderBy('lot_no')
            ->orderBy('id');

        $products = $query->paginate($this->perPage, ['*'], 'page', max(1, $this->page));

        return view('Pharma::livewire.drug-bid-award.product-workspace', [
            'result' => $result,
            'products' => $products,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    private function normalizePerPage(mixed $value): int
    {
        $value = (int) $value;

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
    }
}
