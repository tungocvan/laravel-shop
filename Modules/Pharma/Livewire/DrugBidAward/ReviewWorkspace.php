<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
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

    public array $selectedAwardIds = [];

    public ?int $selectedAwardId = null;

    public string $candidateSearch = '';

    public ?int $selectedMedicineId = null;

    public ?int $selectedVariantId = null;

    public ?int $selectedPackageId = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $linkSuccessModal = false;

    public ?string $linkedMedicineName = null;

    public ?string $linkedMedicineCode = null;

    public ?string $confirmationAction = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selectedAwardIds = [];
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
        $this->selectedAwardIds = [];
    }

    public function selectAward(int $awardId): void
    {
        $award = DrugBidAward::query()->with('canonicalMatch')->findOrFail($awardId);
        $this->selectedAwardId = $award->id;
        $this->selectedMedicineId = $award->canonicalMatch?->medicine_id;
        $this->selectedVariantId = $award->canonicalMatch?->medicine_variant_id;
        $this->selectedPackageId = $award->canonicalMatch?->medicine_package_id;
        $this->candidateSearch = (string) $award->medicine_name;
        $this->reset(['successMessage', 'errorMessage', 'confirmationAction']);
        if (! $this->selectedMedicineId) $this->applyDeterministicDefault($award);
    }

    public function chooseMedicine(int $medicineId): void
    {
        $medicine = Medicine::query()->where('profile_status', Medicine::PROFILE_VERIFIED)->with(['variants.packages'])->findOrFail($medicineId);
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
        if ($variant && $variant->packages->count() === 1) $this->selectedPackageId = $variant->packages->first()->id;
    }

    public function closeReview(): void
    {
        $this->reset(['selectedAwardId', 'selectedMedicineId', 'selectedVariantId', 'selectedPackageId', 'candidateSearch', 'successMessage', 'errorMessage', 'confirmationAction']);
    }

    public function requestLinkSave(): void
    {
        if (! $this->selectedAwardId || ! $this->selectedMedicineId) {
            $this->errorMessage = 'Vui lòng chọn một thuốc trong Danh mục thuốc chuẩn trước khi lưu liên kết.';
            return;
        }
        $this->confirmationAction = 'save_link';
    }

    public function requestUnlink(): void
    {
        $award = $this->selectedAwardId ? DrugBidAward::query()->with('canonicalMatch')->find($this->selectedAwardId) : null;
        if (! $award?->canonicalMatch?->medicine_id) {
            $this->errorMessage = 'Kết quả này hiện chưa có liên kết để hủy.';
            return;
        }
        $this->confirmationAction = 'unlink';
    }

    public function requestBulkUnlink(): void
    {
        $ids = collect($this->selectedAwardIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            $this->errorMessage = 'Vui lòng chọn ít nhất một kết quả đã liên kết để hủy liên kết hàng loạt.';

            return;
        }

        $this->errorMessage = null;
        $this->confirmationAction = 'bulk_unlink';
    }

    public function cancelConfirmation(): void { $this->confirmationAction = null; }

    public function confirmSelection(DrugBidAwardMatchManager $manager): void
    {
        $this->confirmationAction = null;
        $this->errorMessage = null;
        if (! $this->selectedAwardId || ! $this->selectedMedicineId) {
            $this->errorMessage = 'Vui lòng chọn một thuốc trong Danh mục thuốc chuẩn trước khi xác nhận.';
            return;
        }
        $award = DrugBidAward::query()->findOrFail($this->selectedAwardId);
        $medicine = Medicine::query()->findOrFail($this->selectedMedicineId);
        $variant = $this->selectedVariantId ? MedicineVariant::query()->where('medicine_id', $medicine->id)->findOrFail($this->selectedVariantId) : null;
        $package = $this->selectedPackageId && $variant ? MedicinePackage::query()->where('medicine_variant_id', $variant->id)->findOrFail($this->selectedPackageId) : null;
        $level = $package ? DrugBidAwardMatch::LEVEL_PACKAGE : ($variant ? DrugBidAwardMatch::LEVEL_VARIANT : DrugBidAwardMatch::LEVEL_MEDICINE);
        $manager->confirm($award, new DrugBidMatchResult($medicine, $variant, $package, DrugBidAwardMatch::STATUS_EXACT, 'manual_review', 100, $level), auth()->id());
        $this->showActionSuccess('Đã liên kết thành công với Danh mục thuốc chuẩn.', $medicine);
    }

    public function bulkLinkSelected(DrugBidAwardMatchManager $manager): void
    {
        $ids = collect($this->selectedAwardIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            $this->errorMessage = 'Vui lòng chọn ít nhất một kết quả trúng thầu để liên kết hàng loạt.';
            return;
        }
        $linked = 0;
        $skipped = 0;
        foreach (DrugBidAward::query()->whereIn('id', $ids)->get() as $award) {
            $match = $manager->refresh($award);
            if (! $match->medicine_id || ! in_array($match->match_status, [DrugBidAwardMatch::STATUS_EXACT, DrugBidAwardMatch::STATUS_HIGH_CONFIDENCE], true)) {
                $skipped++;
                continue;
            }
            $manager->confirm($award, new DrugBidMatchResult($match->medicine, $match->variant, $match->package, $match->match_status, $match->match_method ?: 'bulk_review', (int) $match->confidence, $match->resolution_level), auth()->id());
            $linked++;
        }
        $this->selectedAwardIds = [];
        $message = "Đã liên kết hàng loạt {$linked} kết quả.";
        if ($skipped > 0) $message .= " {$skipped} kết quả chưa đủ điều kiện xác định nên được giữ lại để rà soát thủ công.";
        $this->showActionSuccess($message);
    }

    public function bulkUnlinkSelected(): void
    {
        $this->confirmationAction = null;
        $ids = collect($this->selectedAwardIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            $this->errorMessage = 'Vui lòng chọn ít nhất một kết quả đã liên kết để hủy liên kết hàng loạt.';

            return;
        }

        $unlinked = DB::transaction(function () use ($ids): int {
            $matches = DrugBidAwardMatch::query()
                ->whereIn('drug_bid_award_id', $ids)
                ->whereNotNull('medicine_id')
                ->where('review_status', DrugBidAwardMatch::REVIEW_CONFIRMED)
                ->lockForUpdate()
                ->get();

            foreach ($matches as $match) {
                DrugBidAward::query()
                    ->whereKey($match->drug_bid_award_id)
                    ->where('medicine_id', $match->medicine_id)
                    ->update([
                        'medicine_id' => null,
                        'medicine_code' => null,
                        'medicine_match_status' => DrugBidAward::MATCH_UNRESOLVED,
                    ]);

                $match->update([
                    'medicine_id' => null,
                    'medicine_variant_id' => null,
                    'medicine_package_id' => null,
                    'match_status' => DrugBidAwardMatch::STATUS_UNMATCHED,
                    'resolution_level' => null,
                    'review_status' => DrugBidAwardMatch::REVIEW_PENDING,
                    'is_manual' => false,
                    'matched_by' => null,
                    'matched_at' => null,
                    'review_reason' => 'bulk_manual_unlink_requires_review',
                ]);
            }

            return $matches->count();
        });

        $skipped = $ids->count() - $unlinked;
        $this->selectedAwardIds = [];
        $message = "Đã hủy liên kết hàng loạt {$unlinked} kết quả và đưa về hàng chờ Cần rà soát.";
        if ($skipped > 0) $message .= " {$skipped} kết quả không còn ở trạng thái liên kết xác nhận nên được bỏ qua.";
        $this->showActionSuccess($message);
    }

    public function unlinkSelection(): void
    {
        $this->confirmationAction = null;
        $award = DrugBidAward::query()->with('canonicalMatch')->findOrFail($this->selectedAwardId);
        $match = $award->canonicalMatch;
        if (! $match?->medicine_id) { $this->errorMessage = 'Kết quả này hiện chưa có liên kết để hủy.'; return; }
        $oldMedicine = $match->medicine;
        DB::transaction(function () use ($award, $match): void {
            $award->update([
                'medicine_id' => null,
                'medicine_code' => null,
                'medicine_match_status' => DrugBidAward::MATCH_UNRESOLVED,
            ]);
            $match->update(['medicine_id' => null, 'medicine_variant_id' => null, 'medicine_package_id' => null, 'match_status' => DrugBidAwardMatch::STATUS_UNMATCHED, 'resolution_level' => null, 'review_status' => DrugBidAwardMatch::REVIEW_PENDING, 'is_manual' => false, 'matched_by' => null, 'matched_at' => null, 'review_reason' => 'manual_unlink_requires_review']);
        });
        $this->showActionSuccess('Đã hủy liên kết. Kết quả đã được đưa trở lại hàng chờ Cần rà soát.', $oldMedicine);
    }

    public function continueReview()
    {
        $this->linkSuccessModal = false;
        $this->linkedMedicineName = null;
        $this->linkedMedicineCode = null;
        return $this->redirectRoute('admin.pharma.drug-bid-awards.review', navigate: true);
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
        if (! $match) { $this->errorMessage = 'Bản ghi chưa có kết quả matching để bỏ qua. Hãy Match lại hoặc chọn thuốc chuẩn.'; return; }
        $match->update(['review_status' => DrugBidAwardMatch::REVIEW_IGNORED]);
        $this->showActionSuccess('Đã bỏ qua kết quả trúng thầu này khỏi hàng chờ rà soát.', $match->medicine);
    }

    public function render()
    {
        $awards = DrugBidAward::query()->with(['canonicalMatch.medicine', 'canonicalMatch.variant', 'canonicalMatch.package'])
            ->when($this->search !== '', function ($query): void { $search = '%'.trim($this->search).'%'; $query->where(fn ($inner) => $inner->where('medicine_name', 'like', $search)->orWhere('registration_or_import_license', 'like', $search)->orWhere('winning_company_name', 'like', $search)->orWhere('decision_number', 'like', $search)); })
            ->when($this->status !== 'all', function ($query): void {
                if ($this->status === 'linked') $query->whereHas('canonicalMatch', fn ($match) => $match->whereNotNull('medicine_id')->where('review_status', DrugBidAwardMatch::REVIEW_CONFIRMED));
                elseif ($this->status === 'unmatched') $query->where(fn ($inner) => $inner->whereDoesntHave('canonicalMatch')->orWhereHas('canonicalMatch', fn ($match) => $match->where('match_status', DrugBidAwardMatch::STATUS_UNMATCHED)));
                else $query->where(fn ($inner) => $inner->whereDoesntHave('canonicalMatch')->orWhereHas('canonicalMatch', fn ($match) => $match->whereIn('review_status', [DrugBidAwardMatch::REVIEW_PENDING, DrugBidAwardMatch::REVIEW_STALE])));
            })->latest('id')->paginate($this->perPage);
        $selectedAward = $this->selectedAwardId ? DrugBidAward::query()->with(['canonicalMatch.medicine', 'canonicalMatch.variant', 'canonicalMatch.package'])->find($this->selectedAwardId) : null;
        $candidates = $selectedAward ? $this->candidateMedicines($selectedAward) : collect();
        $isLinked = (bool) ($selectedAward?->canonicalMatch?->review_status === DrugBidAwardMatch::REVIEW_CONFIRMED && $selectedAward?->canonicalMatch?->medicine_id);
        return view('Pharma::livewire.drug-bid-award.review-workspace', compact('awards', 'selectedAward', 'candidates', 'isLinked'));
    }

    private function showActionSuccess(string $message, ?Medicine $medicine = null): void
    {
        $this->linkedMedicineName = $medicine?->name;
        $this->linkedMedicineCode = $medicine?->medicine_code;
        $this->successMessage = $message;
        $this->linkSuccessModal = true;
        $this->reset(['selectedAwardId', 'selectedMedicineId', 'selectedVariantId', 'selectedPackageId', 'candidateSearch', 'errorMessage', 'confirmationAction']);
    }

    private function candidateMedicines(DrugBidAward $award): EloquentCollection
    {
        $query = Medicine::query()->where('profile_status', Medicine::PROFILE_VERIFIED)->with(['variants.packages']);
        $search = trim($this->candidateSearch);
        if ($search !== '') { $term = '%'.$search.'%'; $query->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('registration_number', 'like', $term)->orWhere('medicine_code', 'like', $term)); }
        elseif ($award->registration_or_import_license) $query->where('registration_number', $award->registration_or_import_license);
        else $query->where('name', 'like', '%'.trim((string) $award->medicine_name).'%');
        return $query->limit(12)->get();
    }

    private function applyDeterministicDefault(DrugBidAward $award): void
    {
        $candidates = collect();
        if ($award->registration_or_import_license) $candidates = Medicine::query()->where('profile_status', Medicine::PROFILE_VERIFIED)->with(['variants.packages'])->where('registration_number', $award->registration_or_import_license)->limit(2)->get();
        if ($candidates->isEmpty()) $candidates = Medicine::query()->where('profile_status', Medicine::PROFILE_VERIFIED)->with(['variants.packages'])->where('name', $award->medicine_name)->limit(2)->get();
        if ($candidates->count() === 1) $this->chooseMedicine($candidates->first()->id);
    }

    private function applySingleVariantAndPackage(Medicine $medicine): void
    {
        if ($medicine->variants->count() !== 1) return;
        $variant = $medicine->variants->first();
        $this->selectedVariantId = $variant->id;
        if ($variant->packages->count() === 1) $this->selectedPackageId = $variant->packages->first()->id;
    }
}
