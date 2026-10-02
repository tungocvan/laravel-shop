<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\InventoryReceipt;
use Modules\Pharma\Models\SupplierTracking;

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

    public function authoringOptions(bool $canViewCosts = false): array
    {
        $medicines = Medicine::query()->orderBy('name')->limit(500)->get(['id', 'medicine_code', 'name', 'unit', 'active_ingredients']);

        $referenceCosts = collect();
        if ($canViewCosts) {
            $today = now()->toDateString();
            $referenceCosts = SupplierTracking::query()
                ->select('medicine_id', DB::raw('AVG(cost_price) as average_cost_price'))
                ->whereIn('medicine_id', $medicines->pluck('id'))
                ->where('status', 'active')
                ->whereNotNull('cost_price')
                ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
                ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
                ->groupBy('medicine_id')
                ->pluck('average_cost_price', 'medicine_id');
        }

        return [
            'suppliers' => Partner::query()->withPartnerType('supplier')->where('status', 'active')->orderBy('name')->get(['id', 'name', 'tax_code']),
            'medicines' => $medicines,
            'referenceCosts' => $referenceCosts,
            'canViewCosts' => $canViewCosts,
        ];
    }

    public function createDraft(array $data, int $userId): InventoryReceipt
    {
        $supplier = Partner::query()->withPartnerType('supplier')->where('status', 'active')->find($data['supplier_id']);
        if (! $supplier) {
            throw ValidationException::withMessages(['supplier_id' => 'Nhà cung cấp không còn hoạt động hoặc không có vai trò supplier.']);
        }

        $duplicateKeys = collect($data['items'])->map(
            fn (array $item): string => (int) $item['medicine_id'].'|'.mb_strtolower(trim($item['batch_number'])).'|'.$item['expiry_date']
        );
        if ($duplicateKeys->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => 'Không được trùng Thuốc + Số lô + Hạn dùng trong cùng phiếu nhập.']);
        }

        return DB::transaction(function () use ($data, $supplier, $userId): InventoryReceipt {
            $warehouse = $this->inventory->defaultWarehouse();
            DB::table('pharma_inventory_warehouses')->where('id', $warehouse->id)->lockForUpdate()->first();

            $receipt = InventoryReceipt::create([
                'warehouse_id' => $warehouse->id,
                'number' => $this->nextNumber(),
                'receipt_date' => $data['receipt_date'],
                'supplier_name' => $supplier->name,
                'invoice_number' => $data['invoice_number'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'status' => InventoryReceipt::DRAFT,
            ]);
            $receipt->items()->createMany($data['items']);

            return $receipt->fresh(['items.medicine']);
        });
    }

    private function nextNumber(): string
    {
        $pattern = 'PN-'.now()->format('ymd').'-';
        $latest = InventoryReceipt::query()->where('number', 'like', $pattern.'%')->orderByDesc('number')->value('number');
        $sequence = $latest ? ((int) substr($latest, -3)) + 1 : 1;
        if ($sequence > 999) {
            throw ValidationException::withMessages(['number' => 'Đã vượt quá 999 chứng từ trong ngày.']);
        }

        return $pattern.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

}
