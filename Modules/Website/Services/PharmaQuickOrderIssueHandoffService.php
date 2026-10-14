<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\PriceListItem;
use Modules\Pharma\Services\UserOrderAuthoringService;
use Modules\Website\Models\PharmaQuickOrderRequest;

final class PharmaQuickOrderIssueHandoffService
{
    public function __construct(
        private readonly PharmaQuickOrderIssueReadinessService $readiness,
        private readonly UserOrderAuthoringService $authoring,
    ) {}

    public function createDraft(int $requestId, int $managerUserId, int $partnerId, int $priceListId, array $selectedItems): InventoryIssue
    {
        return DB::transaction(function () use ($requestId, $managerUserId, $partnerId, $priceListId, $selectedItems): InventoryIssue {
            $request = PharmaQuickOrderRequest::query()->lockForUpdate()->findOrFail($requestId);

            if ($request->inventory_issue_id) {
                return InventoryIssue::query()->findOrFail($request->inventory_issue_id);
            }

            $inspection = $this->readiness->inspect($request);
            if (! $inspection['ready_for_authoring']) {
                throw ValidationException::withMessages(['handoff' => implode(' ', $inspection['problems'])]);
            }
            if (! Partner::query()->withPartnerType('customer')->whereKey($partnerId)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['recipient_partner_id' => 'Khách hàng Pharma không hợp lệ.']);
            }

            $date = now()->toDateString();
            $priceList = $this->authoring->priceLists($managerUserId, $date)->firstWhere('id', $priceListId);
            if (! $priceList || ($priceList->partner_id && (int) $priceList->partner_id !== $partnerId)) {
                throw ValidationException::withMessages(['price_list_id' => 'Bảng giá không còn hiệu lực hoặc không phù hợp khách hàng/người phụ trách.']);
            }

            $expected = collect($inspection['lines']);
            if (count($selectedItems) !== $expected->count()) {
                throw ValidationException::withMessages(['items' => 'Cần chọn đúng một dòng bảng giá cho mỗi thuốc yêu cầu.']);
            }

            $ids = collect($selectedItems)->map(fn ($id) => (int) $id);
            if ($ids->unique()->count() !== $ids->count()) {
                throw ValidationException::withMessages(['items' => 'Không được chọn trùng dòng bảng giá.']);
            }

            $priceItems = PriceListItem::query()->where('price_list_id', $priceListId)
                ->whereIn('id', $ids)->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
                ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
                ->get()->keyBy('id');

            $quantities = [];
            foreach ($expected as $index => $line) {
                $itemId = (int) ($selectedItems[$index] ?? 0);
                $item = $priceItems->get($itemId);
                if (! $item
                    || (int) $item->medicine_id !== (int) $line['medicine_id']
                    || (int) $item->medicine_variant_id !== (int) $line['medicine_variant_id']
                    || (int) $item->medicine_package_id !== (int) $line['medicine_package_id']) {
                    throw ValidationException::withMessages(['items' => 'Dòng '.($index + 1).' không khớp thuốc/quy cách trong bảng giá.']);
                }
                $quantities[$itemId] = (int) $line['quantity'];
            }

            // Reuse canonical authoring validation and keep the issue in draft state.
            $issue = $this->authoring->createDraft($managerUserId, $managerUserId, [
                'source' => 'price_list',
                'issue_date' => $date,
                'price_list_id' => $priceListId,
                'recipient_partner_id' => $partnerId,
                'quantities' => $quantities,
                'notes' => 'Yêu cầu đặt hàng nhanh Website #'.$request->id.'; xác minh thông tin nhận hàng trước khi gửi duyệt.',
            ]);
            $request->forceFill(['inventory_issue_id' => $issue->id])->save();

            return $issue;
        });
    }
}
