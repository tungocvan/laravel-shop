<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\MedicineVariant;
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

    public function products(?string $search = null): Collection
    {
        $search = trim((string) $search);

        return MedicineVariant::query()
            ->with(['medicine:id,name,active_ingredients,registration_number,declared_price,packaging_specification'])
            ->whereHas('medicine', function ($query) use ($search): void {
                $query->where('catalog_status', 'active')
                    ->when($search !== '', function ($query) use ($search): void {
                        $like = "%{$search}%";
                        $query->where(fn ($inner) => $inner->where('name', 'like', $like)
                            ->orWhere('active_ingredients', 'like', $like)
                            ->orWhere('registration_number', 'like', $like));
                    });
            })
            ->orderBy('sku')
            ->limit(100)
            ->get(['id', 'medicine_id', 'sku', 'strength_text', 'presentation_text']);
    }

    public function createDraft(int $userId, array $header, array $items): PriceList
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Vui lòng chọn ít nhất một sản phẩm cho bảng giá.']);
        }

        return DB::transaction(function () use ($userId, $header, $items): PriceList {
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
                $list->items()->create($this->manager->validateItem([
                    'medicine_variant_id' => (int) $item['medicine_variant_id'],
                    'medicine_package_id' => null,
                    'company_sale_price' => $item['company_sale_price'],
                    'actual_receivable_price' => $item['actual_receivable_price'] ?? null,
                    'invoice_price' => $item['invoice_price'] ?? null,
                    'status' => 'active',
                ]));
            }

            return $list->load('items');
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
}
