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

    public int $medicineSearchRevision = 0;

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

    public ?int $editingProductId = null;

    public bool $productSaveModal = false;

    public string $productSaveModalType = 'success';

    public string $productSaveModalMessage = '';

    public function mount(?int $id = null): void
    {
        $id ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();

        if (! $id) {
            return;
        }

        $this->awardId = $id;
        $this->isEditMode = true;
        $service = app(DrugBidAwardService::class);
        $award = $service->findOrFail($id);
        $legalInfo = $service->legalInfoForResultGroup($id);

        $this->medicine_id = $award->medicine_id;
        $this->medicine_name = $award->medicine_name ?? '';
        $this->packaging_specification = $award->packaging_specification ?? '';
        $this->quantity = $award->quantity;
        $this->unit_price = $award->unit_price;
        $this->bidding_notice_code = $legalInfo->bidding_notice_code ?? $award->bidding_notice_code ?? '';
        $this->investor_name = $legalInfo->investor_name ?? $award->investor_name ?? '';
        $this->decision_number = $legalInfo->decision_number ?? $award->decision_number ?? '';
        $this->decision_date = $legalInfo->decision_date?->format('Y-m-d') ?? $award->decision_date?->format('Y-m-d') ?? '';
        $this->contract_duration_months = $service->contractDurationMonthsForResultGroup($id)
            ?? $legalInfo->contract_duration_months
            ?? $award->contract_duration_months;
        $this->winning_company_name = $award->winning_company_name ?? '';
        $this->decision_document_url = $legalInfo->decision_document_url ?? $award->decision_document_url ?? '';
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
        $this->medicineSearchRevision++;

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

    public function editProduct(int $productId, DrugBidAwardService $service): void
    {
        $this->authorizePharmaEdit();
        $product = $service->findProductInResultGroupOrFail($this->awardId, $productId);

        $this->editingProductId = $product->id;
        $this->medicine_id = $product->medicine_id;
        $this->medicine_name = $product->medicine_name ?? '';
        $this->packaging_specification = $product->packaging_specification ?? '';
        $this->quantity = $product->quantity;
        $this->unit_price = $product->unit_price;
        $this->winning_company_name = $product->winning_company_name ?? '';
        $this->medicineSearch = $product->medicine?->name ?? $product->medicine_name;
    }

    public function cancelProductEdit(): void
    {
        $this->editingProductId = null;
        $this->resetValidation();
    }

    public function saveProduct(DrugBidAwardService $service): void
    {
        $this->authorizePharmaEdit();

        if (! $this->editingProductId) {
            return;
        }

        try {
            $this->quantity = $this->normalizeAwardQuantity($this->quantity);
            $this->unit_price = $this->normalizeLocalizedNumber($this->unit_price);

            $data = $this->validate([
                'medicine_id' => 'nullable|exists:pharma_medicines,id',
                'medicine_name' => 'required|string|max:255',
                'packaging_specification' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'unit_price' => 'required|numeric|min:0',
                'winning_company_name' => 'required|string|max:255',
            ]);

            $service->updateProductInResultGroup($this->awardId, $this->editingProductId, $data);
            $this->editingProductId = null;
            $this->productSaveModalType = 'success';
            $this->productSaveModalMessage = 'Cập nhật sản phẩm trúng thầu thành công.';
            $this->productSaveModal = true;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->productSaveModalType = 'error';
            $this->productSaveModalMessage = collect($exception->errors())->flatten()->first()
                ?? 'Dữ liệu sản phẩm chưa hợp lệ. Vui lòng kiểm tra lại.';
            $this->productSaveModal = true;
        } catch (Exception $exception) {
            report($exception);
            $this->productSaveModalType = 'error';
            $this->productSaveModalMessage = 'Không thể cập nhật sản phẩm. Vui lòng thử lại hoặc kiểm tra log hệ thống.';
            $this->productSaveModal = true;
        }
    }

    public function closeProductSaveModal(): void
    {
        $this->productSaveModal = false;
    }

    public function save(DrugBidAwardService $service)
    {
        $this->isEditMode ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();

        $data = $this->isEditMode
            ? $this->validate([
                'bidding_notice_code' => 'required|string|max:100',
                'investor_name' => 'required|string|max:255',
                'decision_number' => 'required|string|max:100',
                'decision_date' => 'required|date',
                'contract_duration_months' => 'required|integer|min:1',
                'decision_document_url' => 'nullable|url|max:255',
            ])
            : $this->validate();

        try {
            if ($this->isEditMode) {
                $service->updateResultGroupLegalInfo($this->awardId, [
                    'bidding_notice_code' => $data['bidding_notice_code'],
                    'investor_name' => $data['investor_name'],
                    'decision_number' => $data['decision_number'],
                    'decision_date' => $data['decision_date'],
                    'contract_duration_months' => $data['contract_duration_months'],
                    'decision_document_url' => $data['decision_document_url'] ?? null,
                ]);
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
            'resultProducts' => $this->isEditMode ? app(DrugBidAwardService::class)->productsForResultGroup($this->awardId) : collect(),
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
            'medicine_code',
            'active_ingredients',
            'concentration',
            'packaging_specification',
        ]);

        if ($search !== '') {
            $query->where(function ($nested) use ($search): void {
                $like = "%{$search}%";
                $nested->where('name', 'like', $like)
                    ->orWhere('registration_number', 'like', $like)
                    ->orWhere('medicine_code', 'like', $like)
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

    private function normalizeAwardQuantity(mixed $value): mixed
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $normalized = $this->normalizeLocalizedNumber($value);

        if (is_numeric($normalized)) {
            return (int) round((float) $normalized);
        }

        return $normalized;
    }

    private function normalizeLocalizedNumber(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        if (preg_match('/^-?\\d{1,3}(?:\\.\\d{3})+$/', $value) === 1) {
            return str_replace('.', '', $value);
        }

        if (preg_match('/^-?\\d{1,3}(?:,\\d{3})+$/', $value) === 1) {
            return str_replace(',', '', $value);
        }

        return $value;
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
                    ->orWhere('medicine_code', 'like', $like)
                    ->orWhere('active_ingredients', 'like', $like);
            })
            ->exists();
    }
}
