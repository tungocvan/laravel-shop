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

    public function updateDraft(int $userId, int $priceListId, array $header, array $items): PriceList
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Vui lòng chọn ít nhất một sản phẩm từ bảng giá gốc.']);
        }

        return DB::transaction(function () use ($userId, $priceListId, $header, $items): PriceList {
            $list = $this->draftForUser($userId, $priceListId);
            $source = $this->sourceForUser($userId, (int) ($header['source_price_list_id'] ?? 0));
            $sourceItems = $source->items()->where('status', 'active')->get()->keyBy('medicine_variant_id');

            $validatedHeader = $this->manager->validateHeader(array_merge($header, [
                'type' => PriceList::TYPE_CUSTOMER,
                'customer_source' => PriceList::CUSTOMER_SOURCE_PARTNER,
                'manager_user_id' => $userId,
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

    public function submit(int $userId, int $priceListId): PriceList
    {
        return DB::transaction(function () use ($userId, $priceListId): PriceList {
            $list = PriceList::query()
                ->where('manager_user_id', $userId)
                ->lockForUpdate()
                ->with('items')
                ->findOrFail($priceListId);

            if ($list->status !== PriceList::STATUS_DRAFT) {
                throw ValidationException::withMessages(['price_list' => 'Chỉ bảng giá Nháp mới được gửi duyệt.']);
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
            ])->save();

            return $list->refresh();
        });
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
