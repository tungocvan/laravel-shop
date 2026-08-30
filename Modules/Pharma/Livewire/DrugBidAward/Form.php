<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Exception;
use Illuminate\Support\Collection;
use Livewire\Component;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\DrugBidAwardService;

class Form extends Component
{
    use AuthorizesPharmaActions;

    private const MEDICINE_RESULT_LIMIT = 25;

    public ?int $awardId = null;

    public bool $isEditMode = false;

    public ?int $medicine_id = null;

    public string $medicineSearch = '';

    public string $medicine_name = '';

    public string $packaging_specification = '';

    public mixed $quantity = '';

    public mixed $unit_price = '';

    public string $bidding_notice_code = '';

    public string $investor_name = '';

    public string $decision_number = '';

    public string $decision_date = '';

    public mixed $contract_duration_months = '';

    public string $winning_company_name = '';

    public string $decision_document_url = '';

    public string $sourceType = DrugBidAward::SOURCE_MANUAL;

    public function mount(?int $id = null): void
    {
        $id ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();

        if (! $id) {
            return;
        }

        $this->awardId = $id;
        $this->isEditMode = true;
        $award = app(DrugBidAwardService::class)->findOrFail($id);

        $this->medicine_id = $award->medicine_id;
        $this->medicine_name = $award->medicine_name;
        $this->packaging_specification = $award->packaging_specification;
        $this->quantity = $award->quantity;
        $this->unit_price = $award->unit_price;
        $this->bidding_notice_code = $award->bidding_notice_code;
        $this->investor_name = $award->investor_name;
        $this->decision_number = $award->decision_number;
        $this->decision_date = $award->decision_date?->format('Y-m-d') ?? '';
        $this->contract_duration_months = $award->contract_duration_months;
        $this->winning_company_name = $award->winning_company_name;
        $this->decision_document_url = $award->decision_document_url ?? '';
        $this->sourceType = $award->source_type ?: DrugBidAward::SOURCE_MANUAL;
        $this->medicineSearch = $award->medicine?->name ?? '';
    }

    protected function rules(): array
    {
        return [
            'medicine_id' => 'nullable|exists:pharma_medicines,id',
            'medicine_name' => 'required|string|max:255',
            'packaging_specification' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'bidding_notice_code' => 'required|string|max:100',
            'investor_name' => 'required|string|max:255',
            'decision_number' => 'required|string|max:100',
            'decision_date' => 'required|date',
            'contract_duration_months' => 'required|integer|min:1',
            'winning_company_name' => 'required|string|max:255',
            'decision_document_url' => 'nullable|url|max:255',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'medicine_name' => 'Tên thuốc thầu',
            'packaging_specification' => 'Quy cách đóng gói',
            'quantity' => 'Số lượng',
            'unit_price' => 'Đơn giá trúng thầu',
            'bidding_notice_code' => 'Mã thông báo mời thầu',
            'investor_name' => 'Tên chủ đầu tư',
            'decision_number' => 'Số quyết định',
            'decision_date' => 'Ngày ban hành',
            'contract_duration_months' => 'Thời hạn hiệu lực',
            'winning_company_name' => 'Công ty trúng thầu',
        ];
    }

    public function updatedMedicineSearch(): void
    {
        if ($this->medicine_id && ! $this->selectedMedicineMatchesSearch()) {
            $this->medicine_id = null;
        }
    }

    public function updatedMedicineId(mixed $value): void
    {
        if (! $value) {
            return;
        }

        $medicine = Medicine::query()->select(['id', 'name', 'packaging_specification'])->find((int) $value);

        if (! $medicine) {
            $this->medicine_id = null;

            return;
        }

        $this->medicineSearch = $medicine->name;

        if ($this->medicine_name === '') {
            $this->medicine_name = $medicine->name;
        }

        if ($this->packaging_specification === '') {
            $this->packaging_specification = $medicine->packaging_specification ?? '';
        }
    }

    public function save(DrugBidAwardService $service)
    {
        $this->isEditMode ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();
        $data = $this->validate();

        try {
            if ($this->isEditMode) {
                $service->update($this->awardId, $data);
                session()->flash('success', 'Cập nhật thông tin trúng thầu thành công.');
            } else {
                $service->store($data);
                session()->flash('success', 'Thêm hồ sơ trúng thầu mới thành công.');
            }

            return redirect()->route('admin.pharma.drug-bid-awards.index');
        } catch (Exception $exception) {
            report($exception);
            session()->flash('error', 'Không thể lưu hồ sơ trúng thầu. Vui lòng thử lại hoặc kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('Pharma::livewire.drug-bid-award.form', [
            'medicines' => $this->medicineCandidates(),
            'medicineResultLimit' => self::MEDICINE_RESULT_LIMIT,
        ]);
    }

    private function medicineCandidates(): Collection
    {
        $search = trim($this->medicineSearch);
        $selectedId = $this->medicine_id;

        if ($search === '' && ! $selectedId) {
            return collect();
        }

        $query = Medicine::query()->select([
            'id',
            'name',
            'registration_number',
            'active_ingredients',
            'concentration',
            'packaging_specification',
        ]);

        if ($search !== '') {
            $query->where(function ($nested) use ($search): void {
                $like = "%{$search}%";
                $nested->where('name', 'like', $like)
                    ->orWhere('registration_number', 'like', $like)
                    ->orWhere('active_ingredients', 'like', $like);
            });
        } else {
            $query->whereKey($selectedId);
        }

        $candidates = $query->orderBy('name')->limit(self::MEDICINE_RESULT_LIMIT)->get();

        if ($selectedId && ! $candidates->contains('id', $selectedId)) {
            $selected = Medicine::query()->select([
                'id',
                'name',
                'registration_number',
                'active_ingredients',
                'concentration',
                'packaging_specification',
            ])->find($selectedId);

            if ($selected) {
                $candidates->prepend($selected);
            }
        }

        return $candidates->unique('id')->values();
    }

    private function selectedMedicineMatchesSearch(): bool
    {
        if (! $this->medicine_id) {
            return false;
        }

        $search = trim($this->medicineSearch);

        if ($search === '') {
            return true;
        }

        return Medicine::query()
            ->whereKey($this->medicine_id)
            ->where(function ($query) use ($search): void {
                $like = "%{$search}%";
                $query->where('name', 'like', $like)
                    ->orWhere('registration_number', 'like', $like)
                    ->orWhere('active_ingredients', 'like', $like);
            })
            ->exists();
    }
}
