<?php

namespace Modules\Pharma\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\PriceList;

final class ApproverGlobalPriceListWorkflow
{
    public function __construct(private readonly PriceListManager $manager) {}

    public function activeUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function createAndActivate(int $approverUserId, bool $applyAllUsers, array $userIds, array $header, array $items): PriceList
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Vui lòng chọn ít nhất một sản phẩm.']);
        }

        $userIds = collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        if (! $applyAllUsers && $userIds === []) {
            throw ValidationException::withMessages(['global_user_ids' => 'Vui lòng chọn ít nhất một User hoặc bật Áp dụng tất cả User.']);
        }
        if (! $applyAllUsers) {
            $activeUserIds = User::query()->whereIn('id', $userIds)->where('is_active', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (count($activeUserIds) !== count($userIds)) {
                throw ValidationException::withMessages(['global_user_ids' => 'Danh sách áp dụng chỉ được chứa User đang hoạt động.']);
            }
        }

        $sourceId = (int) ($header['source_price_list_id'] ?? 0);
        $source = PriceList::query()
            ->whereKey($sourceId)
            ->where('type', PriceList::TYPE_GLOBAL)
            ->activeAt(now())
            ->with('items')
            ->first();

        if (! $source) {
            throw ValidationException::withMessages(['source_price_list_id' => 'Bảng giá gốc phải là bảng giá chung đang ACTIVE.']);
        }

        $sourceItems = $source->items->where('status', 'active')->keyBy('medicine_variant_id');

        return DB::transaction(function () use ($approverUserId, $applyAllUsers, $userIds, $header, $items, $sourceItems): PriceList {
            $validatedHeader = $this->manager->validateHeader(array_merge($header, [
                'type' => PriceList::TYPE_GLOBAL,
                'partner_id' => null,
                'purpose_id' => null,
            ]));

            $list = PriceList::query()->create(array_merge($validatedHeader, [
                'manager_user_id' => null,
                'status' => PriceList::STATUS_DRAFT,
                'created_by' => $approverUserId,
                'submitted_by' => null,
                'submitted_at' => null,
                'approved_by' => null,
                'approved_at' => null,
            ]));

            foreach ($items as $item) {
                $variantId = (int) $item['medicine_variant_id'];
                $sourceItem = $sourceItems->get($variantId);
                if (! $sourceItem) {
                    throw ValidationException::withMessages(['items' => 'Sản phẩm đã chọn không thuộc bảng giá gốc.']);
                }

                $list->items()->create($this->manager->validateItem([
                    'medicine_variant_id' => $variantId,
                    'medicine_package_id' => $sourceItem->medicine_package_id,
                    'declared_price_snapshot' => $sourceItem->declared_price_snapshot,
                    'company_sale_price' => $item['company_sale_price'],
                    'actual_receivable_price' => $item['actual_receivable_price'] ?? $item['company_sale_price'],
                    'invoice_price' => $item['invoice_price'] ?? $item['company_sale_price'],
                    'status' => 'active',
                ]));
            }

            $list->globalUsers()->sync($applyAllUsers ? [] : $userIds);

            return $this->manager->activate($list, $approverUserId)->load(['items', 'globalUsers']);
        });
    }
}
