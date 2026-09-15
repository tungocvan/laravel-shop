<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Pharma\Data\DrugBidMatchResult;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardMatch;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicinePackage;
use Modules\Pharma\Models\MedicineVariant;
use Modules\Pharma\Services\DrugBidAwardMatchManager;

class ReviewWorkspace extends Component
{
    use WithPagination;

    public string $status = 'pending';

    public string $search = '';

    public int $perPage = 25;

    public ?int $selectedAwardId = null;

    public string $candidateSearch = '';

    public ?int $selectedMedicineId = null;

    public ?int $selectedVariantId = null;

    public ?int $selectedPackageId = null;

    public ?string $successMessage = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function selectAward(int $awardId): void
    {
        $award = DrugBidAward::query()->with('canonicalMatch')->findOrFail($awardId);
        $this->selectedAwardId = $award->id;
        $this->selectedMedicineId = $award->canonicalMatch?->medicine_id;
        $this->selectedVariantId = $award->canonicalMatch?->medicine_variant_id;
        $this->selectedPackageId = $award->canonicalMatch?->medicine_package_id;
        $this->candidateSearch = $award->medicine_name;
        $this->successMessage = null;
    }

    public function closeReview(): void
    {
        $this->reset(['selectedAwardId', 'selectedMedicineId', 'selectedVariantId', 'selectedPackageId', 'candidateSearch']);
    }

    public function confirmSelection(DrugBidAwardMatchManager $manager): void
    {
        $award = DrugBidAward::query()->findOrFail($this->selectedAwardId);
        $medicine = Medicine::query()->findOrFail($this->selectedMedicineId);
        $variant = $this->selectedVariantId ? MedicineVariant::query()->where('medicine_id', $medicine->id)->findOrFail($this->selectedVariantId) : null;
        $package = $this->selectedPackageId && $variant
            ? MedicinePackage::query()->where('medicine_variant_id', $variant->id)->findOrFail($this->selectedPackageId)
            : null;

        $level = $package ? DrugBidAwardMatch::LEVEL_PACKAGE : ($variant ? DrugBidAwardMatch::LEVEL_VARIANT : DrugBidAwardMatch::LEVEL_MEDICINE);
        $manager->confirm($award, new DrugBidMatchResult(
            $medicine,
            $variant,
            $package,
            DrugBidAwardMatch::STATUS_EXACT,
            'manual_review',
            100,
            $level,
        ), auth('admin')->id());

        $this->successMessage = 'Đã xác nhận liên kết canonical cho kết quả trúng thầu.';
    }

    public function rematch(DrugBidAwardMatchManager $manager): void
    {
        $award = DrugBidAward::query()->findOrFail($this->selectedAwardId);
        $manager->refresh($award);
        $this->selectAward($award->id);
        $this->successMessage = 'Đã chạy lại canonical matching.';
    }

    public function ignore(): void
    {
        $award = DrugBidAward::query()->findOrFail($this->selectedAwardId);
        $award->canonicalMatch()->update(['review_status' => DrugBidAwardMatch::REVIEW_IGNORED]);
        $this->successMessage = 'Đã bỏ qua kết quả trúng thầu này khỏi hàng chờ rà soát.';
    }

    public function render()
    {
        $awards = DrugBidAward::query()
            ->with(['canonicalMatch.medicine', 'canonicalMatch.variant', 'canonicalMatch.package'])
            ->when($this->search !== '', function ($query): void {
                $search = '%'.trim($this->search).'%';
                $query->where(fn ($inner) => $inner->where('medicine_name', 'like', $search)
                    ->orWhere('registration_or_import_license', 'like', $search)
                    ->orWhere('winning_company_name', 'like', $search)
                    ->orWhere('decision_number', 'like', $search));
            })
            ->when($this->status !== 'all', function ($query): void {
                if ($this->status === 'linked') {
                    $query->whereHas('canonicalMatch', fn ($match) => $match->whereNotNull('medicine_id'));
                } elseif ($this->status === 'unmatched') {
                    $query->whereHas('canonicalMatch', fn ($match) => $match->where('match_status', DrugBidAwardMatch::STATUS_UNMATCHED));
                } else {
                    $query->whereHas('canonicalMatch', fn ($match) => $match->whereIn('review_status', [DrugBidAwardMatch::REVIEW_PENDING, DrugBidAwardMatch::REVIEW_STALE]));
                }
            })
            ->latest('id')
            ->paginate($this->perPage);

        $selectedAward = $this->selectedAwardId
            ? DrugBidAward::query()->with(['canonicalMatch.medicine', 'canonicalMatch.variant', 'canonicalMatch.package'])->find($this->selectedAwardId)
            : null;

        $candidates = collect();
        if ($selectedAward && trim($this->candidateSearch) !== '') {
            $term = '%'.trim($this->candidateSearch).'%';
            $candidates = Medicine::query()->with(['variants.packages'])
                ->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('registration_number', 'like', $term)->orWhere('medicine_code', 'like', $term))
                ->limit(12)->get();
        }

        return view('Pharma::livewire.drug-bid-award.review-workspace', compact('awards', 'selectedAward', 'candidates'));
    }
}
