<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\PriceList;

final class PriceListApprovalWorkflow
{
    public function __construct(private readonly PriceListManager $manager) {}

    public function queue(?string $search = null, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $search = trim((string) $search);

        return PriceList::query()
            ->where('status', PriceList::STATUS_PENDING_APPROVAL)
            ->where('type', PriceList::TYPE_CUSTOMER)
            ->with(['partner', 'purpose', 'manager', 'submitter', 'sourcePriceList'])
            ->withCount('items')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(fn (Builder $inner) => $inner
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('partner', fn (Builder $partner) => $partner->where('name', 'like', "%{$search}%")));
            })
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function pendingCount(): int
    {
        return PriceList::query()
            ->where('status', PriceList::STATUS_PENDING_APPROVAL)
            ->where('type', PriceList::TYPE_CUSTOMER)
            ->count();
    }

    public function findPending(int $priceListId): ?PriceList
    {
        return PriceList::query()
            ->whereKey($priceListId)
            ->where('status', PriceList::STATUS_PENDING_APPROVAL)
            ->where('type', PriceList::TYPE_CUSTOMER)
            ->with([
                'partner', 'purpose', 'manager', 'submitter', 'sourcePriceList',
                'items.variant.medicine', 'items.package',
            ])
            ->withCount('items')
            ->first();
    }

    public function updateItemPrice(int $approverUserId, int $priceListId, int $itemId, float $companySalePrice): PriceList
    {
        return DB::transaction(function () use ($approverUserId, $priceListId, $itemId, $companySalePrice): PriceList {
            $list = PriceList::query()->lockForUpdate()->with('items')->findOrFail($priceListId);
            $this->assertPendingAndIndependent($list, $approverUserId);
            $item = $list->items->firstWhere('id', $itemId);
            if (! $item) {
                throw ValidationException::withMessages(['item' => 'Sản phẩm không thuộc bảng giá đang phê duyệt.']);
            }

            $oldPrice = $item->company_sale_price;
            $validated = $this->manager->validateItem(array_merge($item->toArray(), [
                'company_sale_price' => $companySalePrice,
                'actual_receivable_price' => $companySalePrice,
                'invoice_price' => $companySalePrice,
            ]));
            $item->fill($validated)->save();

            DB::table('pharma_price_list_approval_item_audits')->insert([
                'price_list_id' => $list->id,
                'price_list_item_id' => $item->id,
                'medicine_variant_id' => $item->medicine_variant_id,
                'action' => 'price_changed',
                'old_company_sale_price' => $oldPrice,
                'new_company_sale_price' => $item->company_sale_price,
                'changed_by' => $approverUserId,
                'changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $list->refresh()->load(['items.variant.medicine', 'items.package']);
        });
    }

    public function removeItem(int $approverUserId, int $priceListId, int $itemId): PriceList
    {
        return DB::transaction(function () use ($approverUserId, $priceListId, $itemId): PriceList {
            $list = PriceList::query()->lockForUpdate()->with('items')->findOrFail($priceListId);
            $this->assertPendingAndIndependent($list, $approverUserId);
            if ($list->items->count() <= 1) {
                throw ValidationException::withMessages(['items' => 'Bảng giá phải còn ít nhất một sản phẩm để có thể phê duyệt.']);
            }

            $item = $list->items->firstWhere('id', $itemId);
            if (! $item) {
                throw ValidationException::withMessages(['item' => 'Sản phẩm không thuộc bảng giá đang phê duyệt.']);
            }

            DB::table('pharma_price_list_approval_item_audits')->insert([
                'price_list_id' => $list->id,
                'price_list_item_id' => $item->id,
                'medicine_variant_id' => $item->medicine_variant_id,
                'action' => 'removed',
                'old_company_sale_price' => $item->company_sale_price,
                'new_company_sale_price' => null,
                'changed_by' => $approverUserId,
                'changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $item->delete();

            return $list->refresh()->load(['items.variant.medicine', 'items.package']);
        });
    }

    public function approve(int $approverUserId, int $priceListId): PriceList
    {
        return DB::transaction(function () use ($approverUserId, $priceListId): PriceList {
            $list = PriceList::query()->lockForUpdate()->findOrFail($priceListId);
            $this->assertPendingAndIndependent($list, $approverUserId);

            $list->forceFill([
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            return $this->manager->activate($list, $approverUserId);
        });
    }

    public function reject(int $approverUserId, int $priceListId, string $reason): PriceList
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['rejection_reason' => 'Vui lòng nhập lý do từ chối.']);
        }

        return DB::transaction(function () use ($approverUserId, $priceListId, $reason): PriceList {
            $list = PriceList::query()->lockForUpdate()->findOrFail($priceListId);
            $this->assertPendingAndIndependent($list, $approverUserId);

            $list->forceFill([
                'status' => PriceList::STATUS_REJECTED,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => $approverUserId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return $list->refresh();
        });
    }

    private function assertPendingAndIndependent(PriceList $list, int $approverUserId): void
    {
        if ($list->status !== PriceList::STATUS_PENDING_APPROVAL || $list->type !== PriceList::TYPE_CUSTOMER) {
            throw ValidationException::withMessages(['price_list' => 'Bảng giá không còn ở hàng chờ phê duyệt.']);
        }

        if ((int) $list->created_by === $approverUserId
            || (int) $list->manager_user_id === $approverUserId
            || (int) $list->submitted_by === $approverUserId) {
            throw ValidationException::withMessages([
                'price_list' => 'Người tạo, người phụ trách hoặc người gửi không được tự phê duyệt bảng giá của mình.',
            ]);
        }
    }
}
