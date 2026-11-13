<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\MedicineVariant;
use Modules\Pharma\Models\PriceList;

final class GlobalPriceListRevisionWorkflow
{
    public function __construct(private readonly PriceListManager $manager) {}

    public function createDraft(int $sourceId, int $userId): PriceList
    {
        return DB::transaction(function () use ($sourceId, $userId): PriceList {
            $source = PriceList::query()->lockForUpdate()->findOrFail($sourceId);
            $this->assertGlobalActive($source);
            $revision = $this->manager->clone($source, [
                'code' => $source->code.'-REV-'.strtoupper(\Illuminate\Support\Str::random(8)),
                'name' => $source->name.' (bản cập nhật)',
                'source_price_list_id' => $source->id,
                'created_by' => $userId,
                'submitted_by' => null,
                'submitted_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'deactivated_by' => null,
                'deactivated_at' => null,
            ]);
            $revision->globalUsers()->sync($source->globalUsers()->pluck('users.id')->all());

            return $revision;
        });
    }

    public function addItems(int $revisionId, array $items): PriceList
    {
        return DB::transaction(function () use ($revisionId, $items): PriceList {
            $revision = PriceList::query()->lockForUpdate()->findOrFail($revisionId);
            $this->assertRevisionDraft($revision);
            $existing = $revision->items()->pluck('identity_key')->all();
            foreach ($items as $item) {
                $variant = MedicineVariant::query()->with('medicine')->where('status', 'active')->findOrFail((int) $item['medicine_variant_id']);
                $declared = $variant->medicine?->declared_price;
                $price = $item['company_sale_price'] ?? $declared;
                if ($price === null) {
                    throw ValidationException::withMessages(['items' => 'SKU chưa có giá kê khai: vui lòng nhập Giá bán (VAT).']);
                }
                $payload = $this->manager->validateItem([
                    'medicine_variant_id' => $variant->id,
                    'medicine_package_id' => null,
                    'company_sale_price' => $price,
                    'actual_receivable_price' => $item['actual_receivable_price'] ?? $price,
                    'invoice_price' => $item['invoice_price'] ?? $price,
                    'status' => 'active',
                ]);
                if (in_array($payload['identity_key'], $existing, true)) {
                    throw ValidationException::withMessages(['items' => 'SKU đã có trong bảng giá. Không thể thêm trùng.']);
                }
                $existing[] = $payload['identity_key'];
                $revision->items()->create($payload);
            }

            return $revision->refresh();
        });
    }


    /**
     * Remove one SKU from the existing ACTIVE global list without changing its ID.
     * The approval audit retains the removed SKU and its previous price.
     */
    public function removeActiveItem(int $priceListId, int $itemId, int $actorId): PriceList
    {
        return DB::transaction(function () use ($priceListId, $itemId, $actorId): PriceList {
            $list = PriceList::query()->lockForUpdate()->findOrFail($priceListId);
            $this->assertGlobalActive($list);

            $item = $list->items()->whereKey($itemId)->lockForUpdate()->first();
            if ($item === null) {
                throw ValidationException::withMessages(['item' => 'Sản phẩm không thuộc bảng giá này.']);
            }
            if ($list->items()->count() <= 1) {
                throw ValidationException::withMessages(['items' => 'Bảng giá đang hiệu lực phải còn ít nhất một sản phẩm.']);
            }

            DB::table('pharma_price_list_approval_item_audits')->insert([
                'price_list_id' => $list->id,
                'price_list_item_id' => $item->id,
                'medicine_variant_id' => $item->medicine_variant_id,
                'action' => 'removed',
                'old_company_sale_price' => $item->company_sale_price,
                'new_company_sale_price' => null,
                'changed_by' => $actorId,
                'changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $item->delete();

            return $list->refresh();
        });
    }

    public function activateRevision(int $revisionId, int $approverId): PriceList
    {
        return DB::transaction(function () use ($revisionId, $approverId): PriceList {
            $revision = PriceList::query()->lockForUpdate()->findOrFail($revisionId);
            $this->assertRevisionDraft($revision);
            $source = PriceList::query()->lockForUpdate()->findOrFail($revision->source_price_list_id);
            $this->assertGlobalActive($source);
            if ($revision->items()->count() <= $source->items()->count()) {
                throw ValidationException::withMessages(['items' => 'Phiên bản mới phải có sản phẩm bổ sung.']);
            }
            // Retire the previous version atomically. If activation validation fails,
            // the transaction rolls back and the original remains ACTIVE.
            $this->manager->deactivate($source);
            $revision->forceFill(['submitted_by' => $approverId, 'submitted_at' => now()])->save();
            return $this->manager->activate($revision, $approverId);
        });
    }

    private function assertGlobalActive(PriceList $list): void
    {
        if ($list->type !== PriceList::TYPE_GLOBAL || $list->status !== PriceList::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['price_list' => 'Chỉ có thể tạo phiên bản từ bảng giá chung ACTIVE.']);
        }
    }

    private function assertRevisionDraft(PriceList $list): void
    {
        if ($list->type !== PriceList::TYPE_GLOBAL || $list->status !== PriceList::STATUS_DRAFT || ! $list->source_price_list_id) {
            throw ValidationException::withMessages(['price_list' => 'Chỉ được chỉnh sửa phiên bản bảng giá chung ở trạng thái Nháp.']);
        }
    }
}
