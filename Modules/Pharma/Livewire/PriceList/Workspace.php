<?php

namespace Modules\Pharma\Livewire\PriceList;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Pharma\Data\DrugBidMatchResult;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardMatch;
use Modules\Pharma\Models\MedicinePackage;
use Modules\Pharma\Models\MedicineVariant;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Services\DrugBidAwardMatchManager;

class Workspace extends Create
{
    public ?int $persistedSourceGlobalPriceListId = null;
    public ?string $manualBidKey = null;
    public string $manualBidQuantity = '';
    public string $manualBidPrice = '';
    public string $manualBidDecisionNumber = '';
    public string $manualBidAwardDate = '';
    public string $manualBidContractorName = '';
    public string $manualBidNote = '';

    public function mount(?int $priceListId = null): void
    {
        parent::mount($priceListId);
        if (! $priceListId) return;
        $sourceId = PriceList::query()->whereKey($priceListId)->value('source_price_list_id');
        $this->persistedSourceGlobalPriceListId = $sourceId ? (int) $sourceId : null;
        $this->sourceGlobalPriceListId = $this->persistedSourceGlobalPriceListId;
    }

    public function updatedSourceGlobalPriceListId(mixed $value): void
    {
        if (! $this->priceListId) return;
        $requested = $value === null || $value === '' ? null : (int) $value;
        if ($requested === $this->persistedSourceGlobalPriceListId) return;
        $this->sourceGlobalPriceListId = $this->persistedSourceGlobalPriceListId;
        $this->addError('sourceGlobalPriceListId', 'Nguồn khởi tạo được giữ để truy vết. Khi sửa Draft, danh sách SKU hiện tại mới là dữ liệu chuẩn; không đổi nguồn để tránh chọn lại toàn bộ sản phẩm.');
    }

    public function loadFromGlobalPriceList(): void
    {
        if ($this->priceListId) {
            $this->sourceGlobalPriceListId = $this->persistedSourceGlobalPriceListId;
            $this->successMessage = 'Đang sửa Draft: hệ thống giữ nguyên '.count($this->includedRows).' SKU đã lưu. Nguồn bảng giá chung chỉ dùng để truy vết và không khởi tạo lại selection.';
            $this->step = 3;
            return;
        }
        parent::loadFromGlobalPriceList();
    }

    public function openManualBid(string $key): void
    {
        if (! in_array($key, $this->selectedRows, true)) return;
        $this->resetValidation(['manualBidQuantity', 'manualBidPrice', 'manualBidDecisionNumber', 'manualBidAwardDate', 'manualBidContractorName', 'manualBidNote']);
        $this->manualBidKey = $key;
        $this->manualBidQuantity = '';
        $this->manualBidPrice = '';
        $this->manualBidDecisionNumber = '';
        $this->manualBidAwardDate = now()->toDateString();
        $this->manualBidContractorName = '';
        $this->manualBidNote = '';
    }

    public function closeManualBid(): void
    {
        $this->manualBidKey = null;
        $this->resetValidation(['manualBidQuantity', 'manualBidPrice', 'manualBidDecisionNumber', 'manualBidAwardDate', 'manualBidContractorName', 'manualBidNote']);
    }

    public function saveManualBid(): void
    {
        $this->authorizePharmaCreate();
        $this->validate([
            'manualBidQuantity' => ['required', 'numeric', 'gt:0'],
            'manualBidPrice' => ['required', 'numeric', 'gte:0'],
            'manualBidDecisionNumber' => ['nullable', 'string', 'max:255'],
            'manualBidAwardDate' => ['required', 'date'],
            'manualBidContractorName' => ['required', 'string', 'max:255'],
            'manualBidNote' => ['nullable', 'string', 'max:1000'],
        ]);
        if (! $this->manualBidKey || ! in_array($this->manualBidKey, $this->selectedRows, true)) return;

        [$variantId, $packageId] = array_map('intval', explode('-', $this->manualBidKey, 2));
        $packageId = $packageId === 0 ? null : $packageId;
        $variant = MedicineVariant::query()->with('medicine')->findOrFail($variantId);
        $package = $packageId ? MedicinePackage::query()->where('medicine_variant_id', $variantId)->findOrFail($packageId) : null;
        $medicine = $variant->medicine;
        $key = $this->manualBidKey;

        $award = DB::transaction(function () use ($medicine, $variant, $package): DrugBidAward {
            $award = DrugBidAward::query()->create([
                'canonical_identity_key' => $medicine->canonical_identity_key,
                'medicine_id' => $medicine->id,
                'medicine_code' => $medicine->medicine_code,
                'medicine_match_status' => DrugBidAward::MATCH_VERIFIED,
                'medicine_name' => $medicine->name,
                'active_ingredient' => $medicine->active_ingredients,
                'concentration' => $variant->strength_text ?: $medicine->concentration,
                'route' => $medicine->route_of_administration,
                'dosage_form' => $medicine->dosage_form,
                'unit' => $variant->base_unit ?: $medicine->unit,
                'packaging_specification' => $package?->packaging_text ?: $medicine->packaging_specification,
                'registration_or_import_license' => $medicine->registration_number,
                'manufacturer' => $medicine->manufacturing_company,
                'country' => $medicine->manufacturing_country,
                'quantity' => (float) $this->manualBidQuantity,
                'winning_price' => (float) $this->manualBidPrice,
                'unit_price' => (float) $this->manualBidPrice,
                'decision_number' => trim($this->manualBidDecisionNumber) ?: null,
                'decision_date' => $this->manualBidAwardDate,
                'winning_company_name' => trim($this->manualBidContractorName),
                'is_active' => true,
                'source_type' => DrugBidAward::SOURCE_MANUAL,
                'source_id' => 'manual:'.Str::uuid(),
                'source_synced_at' => now(),
                'created_by' => auth('admin')->id(),
                'manual_note' => trim($this->manualBidNote) ?: null,
            ]);
            $level = $package ? DrugBidAwardMatch::LEVEL_PACKAGE : DrugBidAwardMatch::LEVEL_VARIANT;
            app(DrugBidAwardMatchManager::class)->confirm($award, new DrugBidMatchResult($medicine, $variant, $package, DrugBidAwardMatch::STATUS_EXACT, 'manual_price_list', 100, $level), auth('admin')->id());
            return $award;
        });

        $this->selectedBidAwardIds[$key] = $award->id;
        $this->manualBidKey = null;
        $this->refreshBidIntelligenceForWorkspace();
        $this->successMessage = 'Đã bổ sung kết quả trúng thầu thủ công và liên kết với Medicine Master. Dữ liệu này chỉ dùng làm evidence tham khảo.';
    }

    public function saveDraft(): void
    {
        parent::saveDraft();
        if (! $this->savedModal || ! $this->priceListId) return;
        $sourceId = $this->type === PriceList::TYPE_CUSTOMER ? $this->sourceGlobalPriceListId : null;
        PriceList::query()->whereKey($this->priceListId)->update(['source_price_list_id' => $sourceId]);
        $this->persistedSourceGlobalPriceListId = $sourceId;
    }

    public function render(): View
    {
        $base = parent::render();
        return view('Pharma::livewire.price-list.workspace-bid', $base->getData());
    }

    private function refreshBidIntelligenceForWorkspace(): void
    {
        $items = collect($this->selectedRows)->map(function (string $key): array {
            [$variantId, $packageId] = array_map('intval', explode('-', $key, 2));
            return ['variant_id' => $variantId, 'package_id' => $packageId === 0 ? null : $packageId];
        })->all();
        $intelligence = $this->bidService->forItems($items, 20);
        $result = [];
        foreach ($this->selectedRows as $key) {
            [$variantId, $packageId] = array_map('intval', explode('-', $key, 2));
            $summary = $intelligence['variant:'.$variantId.':package:'.$packageId] ?? null;
            if (! $summary || $summary->count === 0) { $result[$key] = ['has_award' => false, 'history' => []]; continue; }
            $history = $summary->recentAwards->map(fn ($award): array => ['id' => (int) $award->id, 'price' => $award->winning_price, 'quantity' => $award->quantity, 'decision_number' => $award->decision_number, 'award_date' => $award->decision_date?->toDateString() ?? $award->published_at?->toDateString(), 'contractor' => $award->winning_company_name, 'source' => $award->source_type === DrugBidAward::SOURCE_MANUAL ? 'Nhập thủ công' : 'Mua sắm công'])->values()->all();
            $selectedId = $this->selectedBidAwardIds[$key] ?? ($history[0]['id'] ?? null);
            if ($selectedId) $this->selectedBidAwardIds[$key] = $selectedId;
            $result[$key] = ['has_award' => true, 'selected' => collect($history)->firstWhere('id', $selectedId) ?? ($history[0] ?? null), 'history' => $history, 'count' => $summary->count];
        }
        $this->bidIntelligence = $result;
    }
}
