<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Pharma\Models\InventoryReceipt;

final class UserInventoryReceiptWorkspace
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function browse(?string $search = null, ?string $status = null, int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        $warehouse = $this->inventory->defaultWarehouse();

        return InventoryReceipt::query()
            ->withCount('items')
            ->withSum('items as total_quantity', 'quantity')
            ->where('warehouse_id', $warehouse->id)
            ->when(filled($search), fn ($query) => $query->where(fn ($scope) => $scope
                ->where('number', 'like', '%'.trim((string) $search).'%')
                ->orWhere('supplier_name', 'like', '%'.trim((string) $search).'%')
                ->orWhere('invoice_number', 'like', '%'.trim((string) $search).'%')))
            ->when(in_array($status, [InventoryReceipt::DRAFT, InventoryReceipt::POSTED, InventoryReceipt::CANCELLED], true), fn ($query) => $query->where('status', $status))
            ->latest('receipt_date')->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function find(int $receiptId): ?InventoryReceipt
    {
        $warehouse = $this->inventory->defaultWarehouse();

        return InventoryReceipt::query()
            ->with('items.medicine')
            ->where('warehouse_id', $warehouse->id)
            ->find($receiptId);
    }
}
