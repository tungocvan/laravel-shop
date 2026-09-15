<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
    public ?string $errorMessage = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }

    public function selectAward(int $awardId): void
    {
        $award = DrugBidAward::query()->with('canonicalMatch')->findOrFail($awardId);
        $this->selectedAwardId = $award->id;
        $this->selectedMedicineId = $award->canonicalMatch?->medicine_id;
        $this->selectedVariantId = $award->canonicalMatch?->medicine_variant_id;
        $this->selectedPackageId = $award->canonicalMatch?->medicine_package_id;
        $this->candidateSearch = (string) $award->medicine_name;
        $this->reset(['successMessage', 'errorMessage']);

        if (! $this->selectedMedicineId) {
            $this->applyDeterministicDefault($award);
        }
    }

    public function chooseMedicine(int $medicineId): void
    {
        $medicine = Medicine::query()->with(['variants.packages'])->findOrFail($medicineId);
        $this->selectedMedicineId = $medicine->id;
        $this->selectedVariantId = null;
        $this->selectedPackageId = null;
        $this->applySingleVariantAndPackage($medicine);
        $this->errorMessage = null;
    }

    public function updatedSelectedVariantId($value): void
    {
        $this->selectedPackageId = null;
        if (! $value || ! $this->selectedMedicineId) return;

        $variant = MedicineVariant::query()->where('medicine_id', $this->selectedMedicineId)->with('packages')->find($value);
        if ($variant && $variant->packages->count() === 1) {
            $this->selectedPackageId = $variant->packages->first()->id;
        }
    }

    public function closeReview(): void
    {
        $this->reset(['selectedAwardId', 'selectedMedicineId', 'selectedVariantId', 'selectedPackageId', 'candidateSearch', 'successMessage', 'errorMessage']);
    }

    public function confirmSelection(DrugBidAwardMatchManager $manager): void
    {
        $this->errorMessage = null;
        if (! $this->selectedAwardId || ! $this->selectedMedicineId) {
            $this->errorMessage = 'Vui lòng chọn một thuốc trong Danh mục thuốc chuẩn trước khi xác nhận.';
            return;
        }

        $award = DrugBidAward::query()->findOrFail($this->selectedAwardId);
        $medicine = Medicine::query()->findOrFail($this->selectedMedicineId);
        $variant = $this->selectedVariantId
            ? MedicineVariant::query()->where('medicine_id', $medicine->id)->findOrFail($this->selectedVariantId)
            : null;
        $package = $this->selectedPackageId && $variant
            ? MedicinePackage::query()->where('medicine_variant_id', $variant->id)->findOrFail($this->selectedPackageId)
            : null;

        $level = $package ? DrugBidAwardMatch::LEVEL_PACKAGE : ($variant ? DrugBidAwardMatch::LEVEL_VARIANT : DrugBidAwardMatch::LEVEL_MEDICINE);
        $manager->confirm($award, new DrugBidMatchResult($medicine, $variant, $package, DrugBidAwardMatch::STATUS_EXACT, 'manual_review', 100, $level), auth()->id());

        $this->successMessage = 'Đã xác nhận liên kết với Danh mục thuốc chuẩn.';
        $this->selectAward($award->id);
        $this->successMessage = 'Đã xác nhận liên kết với Danh mục thuốc chuẩn.';
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
        $match = $award->canonicalMatch()->first();
        if ($match) {
            $match->update(['review_status' => DrugBidAwardMatch::REVIEW_IGNORED]);
        } else {
            $this->errorMessage = 'Bản ghi chưa có kết quả matching để bỏ qua. Hãy Match lại hoặc chọn thuốc chuẩn.';
            return;
        }
        $this->successMessage = 'Đã bỏ qua kết quả trúng thầu này khỏi hàng chờ rà soát.';
    }

    public function render()
    {
        $awards = DrugBidAward::query()
            ->with(['canonicalMatch.medicine', 'canonicalMatch.variant', 'canonicalMatch.package'])
            ->when($this->search !== '', function ($query): void {
                $search = '%'.trim($this->search).'%';
                $query->where(fn ($inner) => $inner->where('medicine_name', 'like', $search)->orWhere('registration_or_import_license', 'like', $search)->orWhere('winning_company_name', 'like', $search)->orWhere('decision_number', 'like', $search));
            })
            ->when($this->status !== 'all', function ($query): void {
                if ($this->status === 'linked') {
                    $query->whereHas('canonicalMatch', fn ($match) => $match->whereNotNull('medicine_id'));
                } elseif ($this->status === 'unmatched') {
                    $query->where(fn ($inner) => $inner->whereDoesntHave('canonicalMatch')->orWhereHas('canonicalMatch', fn ($match) => $match->where('match_status', DrugBidAwardMatch::STATUS_UNMATCHED)));
                } else {
                    $query->where(fn ($inner) => $inner->whereDoesntHave('canonicalMatch')->orWhereHas('canonicalMatch', fn ($match) => $match->whereIn('review_status', [DrugBidAwardMatch::REVIEW_PENDING, DrugBidAwardMatch::REVIEW_STALE])));
                }
            })->latest('id')->paginate($this->perPage);

        $selectedAward = $this->selectedAwardId ? DrugBidAward::query()->with(['canonicalMatch.medicine', 'canonicalMatch.variant', 'canonicalMatch.package'])->find($this->selectedAwardId) : null;
        $candidates = $selectedAward ? $this->candidateMedicines($selectedAward) : collect();

        return view('Pharma::livewire.drug-bid-award.review-workspace', compact('awards', 'selectedAward', 'candidates'));
    }

    private function candidateMedicines(DrugBidAward $award): EloquentCollection
    {
        $query = Medicine::query()->with(['variants.packages']);
        $search = trim($this->candidateSearch);
        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('registration_number', 'like', $term)->orWhere('medicine_code', 'like', $term));
        } elseif ($award->registration_or_import_license) {
            $query->where('registration_number', $award->registration_or_import_license);
        } else {
            $query->where('name', 'like', '%'.trim((string) $award->medicine_name).'%');
        }

        return $query->limit(12)->get();
    }

    private function applyDeterministicDefault(DrugBidAward $award): void
    {
        $candidates = collect();
        if ($award->registration_or_import_license) {
            $candidates = Medicine::query()->with(['variants.packages'])->where('registration_number', $award->registration_or_import_license)->limit(2)->get();
        }
        if ($candidates->isEmpty()) {
            $candidates = Medicine::query()->with(['variants.packages'])->where('name', $award->medicine_name)->limit(2)->get();
        }
        if ($candidates->count() === 1) {
            $this->chooseMedicine($candidates->first()->id);
        }
    }

    private function applySingleVariantAndPackage(Medicine $medicine): void
    {
        if ($medicine->variants->count() !== 1) return;
        $variant = $medicine->variants->first();
        $this->selectedVariantId = $variant->id;
        if ($variant->packages->count() === 1) {
            $this->selectedPackageId = $variant->packages->first()->id;
        }
    }
}
