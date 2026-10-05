<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\PriceListPurpose;

final class UserPriceListWorkflow
{
    public function __construct(private readonly PriceListManager $manager) {}

    public function customers(): Collection
    {
        return Partner::query()
            ->where('status', 'active')
            ->whereJsonContains('partner_types', 'customer')
            ->orderBy('name')
            ->get(['id', 'name', 'tax_code']);
    }

    public function purposes(): Collection
    {
        return PriceListPurpose::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function sourcePriceLists(int $userId): Collection
    {
        return PriceList::query()
            ->where('type', PriceList::TYPE_GLOBAL)
            ->activeAt(now())
            ->where(function ($query) use ($userId): void {
                $query->whereDoesntHave('globalUsers')
                    ->orWhereHas('globalUsers', fn ($users) => $users->whereKey($userId));
            })
            ->withCount('items')
            ->orderByDesc('effective_from')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'effective_from', 'effective_to']);
    }

    public function sourceProducts(int $userId, int $sourcePriceListId): Collection
    {
        $source = $this->sourceForUser($userId, $sourcePriceListId);

        return $source->items()
            ->with(['variant.medicine', 'package'])
            ->where('status', 'active')
            ->orderBy('id')
            ->get();
    }

    public function editableProducts(int $userId, PriceList $priceList, ?int $requestedSourcePriceListId = null): Collection
    {
        $sourcePriceListId = $requestedSourcePriceListId ?: (int) $priceList->source_price_list_id;

        if ($sourcePriceListId > 0) {
            try {
                return $this->sourceProducts($userId, $sourcePriceListId);
            } catch (ValidationException) {
                // Admin treats the Draft's persisted SKU rows as canonical during edit.
                // Keep that parity when the historical source is no longer ACTIVE/assigned.
            }
        }

        return $priceList->items
            ->filter(fn ($item) => $item->variant !== null)
            ->map(function ($item) {
                $sourceItem = clone $item;
                $sourceItem->setRelation('variant', $item->variant);
                $sourceItem->setRelation('package', $item->package);

                return $sourceItem;
            })
            ->values();
    }

    public function createDraft(int $userId, array $header, array $items): PriceList
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Vui lòng chọn ít nhất một sản phẩm từ bảng giá gốc.']);
        }

        $source = $this->sourceForUser($userId, (int) ($header['source_price_list_id'] ?? 0));
        $sourceItems = $source->items()->where('status', 'active')->get()->keyBy('medicine_variant_id');

        return DB::transaction(function () use ($userId, $header, $items, $sourceItems): PriceList {
            $header = $this->manager->validateHeader(array_merge($header, [
                'type' => PriceList::TYPE_CUSTOMER,
                'customer_source' => PriceList::CUSTOMER_SOURCE_PARTNER,
                'manager_user_id' => $userId,
            ]));

            $list = PriceList::query()->create(array_merge($header, [
                'status' => PriceList::STATUS_DRAFT,
                'created_by' => $userId,
                'approved_by' => null,
                'approved_at' => null,
            ]));

            foreach ($items as $item) {
                $variantId = (int) $item['medicine_variant_id'];
                $sourceItem = $sourceItems->get($variantId);
                if (! $sourceItem) {
                    throw ValidationException::withMessages(['items' => 'Sản phẩm đã chọn không thuộc bảng giá gốc được phép sử dụng.']);
                }

                $list->items()->create($this->manager->validateItem([
                    'medicine_variant_id' => $variantId,
                    'medicine_package_id' => $sourceItem->medicine_package_id,
                    'declared_price_snapshot' => $sourceItem->declared_price_snapshot,
                    'company_sale_price' => $item['company_sale_price'],
                    'actual_receivable_price' => $item['actual_receivable_price'] ?? $sourceItem->actual_receivable_price,
                    'invoice_price' => $item['invoice_price'] ?? $sourceItem->invoice_price,
                    'status' => 'active',
                ]));
            }

            return $list->load('items');
        });
    }

    public function updateDraft(int $userId, int $priceListId, array $header, array $items, bool $approverScope = false): PriceList
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Vui lòng chọn ít nhất một sản phẩm từ bảng giá gốc.']);
        }

        return DB::transaction(function () use ($userId, $priceListId, $header, $items, $approverScope): PriceList {
            $list = $this->editableForUser($userId, $priceListId, $approverScope);
            $requestedSourcePriceListId = (int) ($header['source_price_list_id'] ?? 0);
            $sourceItems = collect();

            try {
                $source = $this->sourceForUser($userId, $requestedSourcePriceListId);
                $sourceItems = $source->items()->where('status', 'active')->get()->keyBy('medicine_variant_id');
            } catch (ValidationException) {
                if ($requestedSourcePriceListId !== (int) $list->source_price_list_id) {
                    throw ValidationException::withMessages(['source_price_list_id' => 'Bảng giá gốc không còn được phép sử dụng.']);
                }

                $sourceItems = $list->items()->get()->keyBy('medicine_variant_id');
            }

            $validatedHeader = $this->manager->validateHeader(array_merge($header, [
                'type' => PriceList::TYPE_CUSTOMER,
                'customer_source' => PriceList::CUSTOMER_SOURCE_PARTNER,
                'manager_user_id' => $list->manager_user_id ?: $userId,
            ]), $list);

            $list->fill($validatedHeader);
            $list->save();
            $list->items()->delete();

            foreach ($items as $item) {
                $variantId = (int) $item['medicine_variant_id'];
                $sourceItem = $sourceItems->get($variantId);
                if (! $sourceItem) {
                    throw ValidationException::withMessages(['items' => 'Sản phẩm đã chọn không thuộc bảng giá gốc được phép sử dụng.']);
                }

                $list->items()->create($this->manager->validateItem([
                    'medicine_variant_id' => $variantId,
                    'medicine_package_id' => $sourceItem->medicine_package_id,
                    'declared_price_snapshot' => $sourceItem->declared_price_snapshot,
                    'company_sale_price' => $item['company_sale_price'],
                    'actual_receivable_price' => $item['actual_receivable_price'] ?? $sourceItem->actual_receivable_price,
                    'invoice_price' => $item['invoice_price'] ?? $sourceItem->invoice_price,
                    'status' => 'active',
                ]));
            }

            return $list->refresh()->load('items');
        });
    }

    public function deleteDraft(int $userId, int $priceListId): void
    {
        DB::transaction(function () use ($userId, $priceListId): void {
            $list = $this->draftForUser($userId, $priceListId);
            if ($list->submitted_at !== null) {
                throw ValidationException::withMessages(['price_list' => 'Bảng giá đã từng gửi duyệt không được xóa.']);
            }
            $list->delete();
        });
    }

    public function submit(int $userId, int $priceListId, bool $approverScope = false): PriceList
    {
        return DB::transaction(function () use ($userId, $priceListId, $approverScope): PriceList {
            $query = PriceList::query()
                ->lockForUpdate()
                ->with('items');

            if (! $approverScope) {
                $query->where('manager_user_id', $userId);
            }

            $list = $query->findOrFail($priceListId);

            if (! in_array($list->status, [PriceList::STATUS_DRAFT, PriceList::STATUS_REJECTED], true)) {
                throw ValidationException::withMessages(['price_list' => 'Chỉ bảng giá Nháp hoặc đã bị từ chối mới được gửi duyệt.']);
            }
            if ($list->items->isEmpty()) {
                throw ValidationException::withMessages(['price_list' => 'Bảng giá phải có ít nhất một sản phẩm trước khi gửi duyệt.']);
            }

            $this->sourceForUser($userId, (int) $list->source_price_list_id);
            $this->manager->validateHeader($list->toArray(), $list);
            foreach ($list->items as $item) {
                if ($item->company_sale_price === null) {
                    throw ValidationException::withMessages(['price_list' => 'Tất cả sản phẩm phải có Giá bán CT trước khi gửi duyệt.']);
                }
                $this->manager->validateItem($item->toArray());
            }

            $list->forceFill([
                'status' => PriceList::STATUS_PENDING_APPROVAL,
                'submitted_by' => $userId,
                'submitted_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            return $list->refresh();
        });
    }

    private function editableForUser(int $userId, int $priceListId, bool $approverScope = false): PriceList
    {
        $query = PriceList::query()
            ->whereIn('status', [PriceList::STATUS_DRAFT, PriceList::STATUS_REJECTED]);

        if (! $approverScope) {
            $query->where('manager_user_id', $userId);
        }

        $list = $query->find($priceListId);

        if (! $list) {
            throw ValidationException::withMessages(['price_list' => 'Chỉ bảng giá Nháp hoặc đã bị từ chối của bạn mới được sửa.']);
        }

        return $list;
    }

    private function draftForUser(int $userId, int $priceListId): PriceList
    {
        $list = PriceList::query()
            ->where('manager_user_id', $userId)
            ->where('status', PriceList::STATUS_DRAFT)
            ->find($priceListId);

        if (! $list) {
            throw ValidationException::withMessages(['price_list' => 'Chỉ bảng giá Nháp của bạn mới được sửa hoặc xóa.']);
        }

        return $list;
    }

    private function sourceForUser(int $userId, int $sourcePriceListId): PriceList
    {
        $source = PriceList::query()
            ->whereKey($sourcePriceListId)
            ->where('type', PriceList::TYPE_GLOBAL)
            ->activeAt(now())
            ->where(function ($query) use ($userId): void {
                $query->whereDoesntHave('globalUsers')
                    ->orWhereHas('globalUsers', fn ($users) => $users->whereKey($userId));
            })
            ->first();

        if (! $source) {
            throw ValidationException::withMessages([
                'source_price_list_id' => 'Bảng giá gốc phải là bảng giá chung đang ACTIVE và được Admin cấp cho User.',
            ]);
        }

        return $source;
    }
}
