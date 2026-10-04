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
                ->orWhere('invoice_number', 'like', '%'.trim((string) $search).'%')
                ->orWhere('invoice_symbol', 'like', '%'.trim((string) $search).'%')))
            ->when(in_array($status, [InventoryReceipt::DRAFT, InventoryReceipt::PENDING_APPROVAL, InventoryReceipt::APPROVED, InventoryReceipt::POSTED, InventoryReceipt::CANCELLED], true), fn ($query) => $query->where('status', $status))
            ->latest('receipt_date')->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function statusCounts(?string $search = null): array
    {
        $warehouse = $this->inventory->defaultWarehouse();

        $query = InventoryReceipt::query()
            ->where('warehouse_id', $warehouse->id)
            ->when(filled($search), fn ($query) => $query->where(fn ($scope) => $scope
                ->where('number', 'like', '%'.trim((string) $search).'%')
                ->orWhere('supplier_name', 'like', '%'.trim((string) $search).'%')
                ->orWhere('invoice_number', 'like', '%'.trim((string) $search).'%')
                ->orWhere('invoice_symbol', 'like', '%'.trim((string) $search).'%')));

        $counts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'all' => (int) $counts->sum(),
            InventoryReceipt::DRAFT => (int) ($counts[InventoryReceipt::DRAFT] ?? 0),
            InventoryReceipt::PENDING_APPROVAL => (int) ($counts[InventoryReceipt::PENDING_APPROVAL] ?? 0),
            InventoryReceipt::APPROVED => (int) ($counts[InventoryReceipt::APPROVED] ?? 0),
            InventoryReceipt::POSTED => (int) ($counts[InventoryReceipt::POSTED] ?? 0),
            InventoryReceipt::CANCELLED => (int) ($counts[InventoryReceipt::CANCELLED] ?? 0),
        ];
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

        $data['items'] = collect($data['items'])->map(fn (array $item): array => [...$item, 'vat_rate' => (float) $data['vat_rate']])->all();

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
                'invoice_symbol' => $data['invoice_symbol'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'status' => InventoryReceipt::DRAFT,
            ]);
            $receipt->items()->createMany($data['items']);

            return $receipt->fresh(['items.medicine']);
        });
    }

    public function updateDraft(InventoryReceipt $receipt, array $data): InventoryReceipt
    {
        return DB::transaction(function () use ($receipt, $data): InventoryReceipt {
            $locked = InventoryReceipt::query()->lockForUpdate()->findOrFail($receipt->getKey());
            if ($locked->status !== InventoryReceipt::DRAFT) {
                throw ValidationException::withMessages(['receipt' => 'Chỉ phiếu nhập nháp mới được sửa.']);
            }
            $supplier = Partner::query()->withPartnerType('supplier')->where('status', 'active')->find($data['supplier_id']);
            if (! $supplier) {
                throw ValidationException::withMessages(['supplier_id' => 'Nhà cung cấp không còn hoạt động hoặc không có vai trò supplier.']);
            }
            $data['items'] = collect($data['items'])->map(fn (array $item): array => [...$item, 'vat_rate' => (float) $data['vat_rate']])->all();
            $duplicateKeys = collect($data['items'])->map(fn (array $item): string => (int) $item['medicine_id'].'|'.mb_strtolower(trim($item['batch_number'])).'|'.$item['expiry_date']);
            if ($duplicateKeys->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['items' => 'Không được trùng Thuốc + Số lô + Hạn dùng trong cùng phiếu nhập.']);
            }
            $locked->update([
                'receipt_date' => $data['receipt_date'], 'supplier_name' => $supplier->name,
                'invoice_number' => $data['invoice_number'] ?? null, 'invoice_symbol' => $data['invoice_symbol'] ?? null, 'invoice_date' => $data['invoice_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $locked->items()->delete();
            $locked->items()->createMany($data['items']);
            return $locked->fresh(['items.medicine']);
        });
    }

    public function deleteDraft(InventoryReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt): void {
            $locked = InventoryReceipt::query()->lockForUpdate()->findOrFail($receipt->getKey());
            if ($locked->status !== InventoryReceipt::DRAFT) {
                throw ValidationException::withMessages(['receipt' => 'Chỉ phiếu nhập nháp mới được xóa.']);
            }
            $locked->items()->delete();
            $locked->delete();
        });
    }

    public function submit(InventoryReceipt $receipt, int $userId): void
    {
        DB::transaction(function () use ($receipt, $userId): void {
            $locked = InventoryReceipt::query()->lockForUpdate()->findOrFail($receipt->getKey());
            if ($locked->status !== InventoryReceipt::DRAFT) {
                throw ValidationException::withMessages(['status' => 'Chỉ phiếu nhập nháp mới được gửi duyệt.']);
            }
            if (! $locked->items()->exists()) {
                throw ValidationException::withMessages(['items' => 'Phiếu nhập phải có ít nhất một dòng.']);
            }
            $locked->update([
                'status' => InventoryReceipt::PENDING_APPROVAL,
                'submitted_by' => $userId, 'submitted_at' => now(),
                'approved_by' => null, 'approved_at' => null,
            ]);
        });
    }

    public function undoSubmit(InventoryReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt): void {
            $locked = InventoryReceipt::query()->lockForUpdate()->findOrFail($receipt->getKey());
            if ($locked->status !== InventoryReceipt::PENDING_APPROVAL) {
                throw ValidationException::withMessages(['status' => 'Chỉ phiếu đang chờ duyệt mới được hoàn tác gửi duyệt.']);
            }
            $locked->update(['status' => InventoryReceipt::DRAFT, 'submitted_by' => null, 'submitted_at' => null]);
        });
    }

    public function approve(InventoryReceipt $receipt, int $userId): void
    {
        DB::transaction(function () use ($receipt, $userId): void {
            $locked = InventoryReceipt::query()->lockForUpdate()->findOrFail($receipt->getKey());
            if (! in_array($locked->status, [InventoryReceipt::DRAFT, InventoryReceipt::PENDING_APPROVAL], true)) {
                throw ValidationException::withMessages(['status' => 'Chỉ phiếu nháp hoặc phiếu chờ duyệt legacy mới được phê duyệt.']);
            }
            if (! $locked->items()->exists()) {
                throw ValidationException::withMessages(['items' => 'Phiếu nhập phải có ít nhất một dòng trước khi phê duyệt.']);
            }
            $locked->update([
                'status' => InventoryReceipt::APPROVED,
                'submitted_by' => $locked->submitted_by ?: $userId,
                'submitted_at' => $locked->submitted_at ?: now(),
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
        });
    }

    public function undoApproval(InventoryReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt): void {
            $locked = InventoryReceipt::query()->lockForUpdate()->findOrFail($receipt->getKey());
            if ($locked->status !== InventoryReceipt::APPROVED) {
                throw ValidationException::withMessages(['status' => 'Chỉ phiếu đã duyệt mới được hoàn tác duyệt.']);
            }
            $locked->update([
                'status' => InventoryReceipt::DRAFT,
                'submitted_by' => null,
                'submitted_at' => null,
                'approved_by' => null,
                'approved_at' => null,
            ]);
        });
    }

    public function post(InventoryReceipt $receipt, int $userId): void
    {
        $locked = InventoryReceipt::query()->findOrFail($receipt->getKey());
        if ($locked->status !== InventoryReceipt::APPROVED) {
            throw ValidationException::withMessages(['status' => 'Chỉ phiếu nhập đã duyệt mới được ghi sổ.']);
        }
        $this->inventory->postReceipt($locked, $userId);
    }

    public function revertPost(InventoryReceipt $receipt, int $userId): void
    {
        $this->inventory->revertReceipt($receipt, $userId);
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
