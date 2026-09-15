<?php

namespace Modules\Pharma\Livewire\PriceList;

use Illuminate\Contracts\View\View;
use Modules\Pharma\Models\PriceList;

class Workspace extends Create
{
    public ?int $persistedSourceGlobalPriceListId = null;

    public function mount(?int $priceListId = null): void
    {
        parent::mount($priceListId);

        if (! $priceListId) {
            return;
        }

        $sourceId = PriceList::query()->whereKey($priceListId)->value('source_price_list_id');
        $this->persistedSourceGlobalPriceListId = $sourceId ? (int) $sourceId : null;
        $this->sourceGlobalPriceListId = $this->persistedSourceGlobalPriceListId;
    }

    public function updatedSourceGlobalPriceListId(mixed $value): void
    {
        if (! $this->priceListId) {
            return;
        }

        $requested = $value === null || $value === '' ? null : (int) $value;
        if ($requested === $this->persistedSourceGlobalPriceListId) {
            return;
        }

        $this->sourceGlobalPriceListId = $this->persistedSourceGlobalPriceListId;
        $this->addError(
            'sourceGlobalPriceListId',
            'Nguồn khởi tạo được giữ để truy vết. Khi sửa Draft, danh sách SKU hiện tại mới là dữ liệu chuẩn; không đổi nguồn để tránh chọn lại toàn bộ sản phẩm.'
        );
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

    public function saveDraft(): void
    {
        parent::saveDraft();

        if (! $this->savedModal || ! $this->priceListId) {
            return;
        }

        $sourceId = $this->type === PriceList::TYPE_CUSTOMER
            ? $this->sourceGlobalPriceListId
            : null;

        PriceList::query()->whereKey($this->priceListId)->update([
            'source_price_list_id' => $sourceId,
        ]);

        $this->persistedSourceGlobalPriceListId = $sourceId;
    }

    public function render(): View
    {
        $base = parent::render();

        return view('Pharma::livewire.price-list.workspace-bid', $base->getData());
    }
}
