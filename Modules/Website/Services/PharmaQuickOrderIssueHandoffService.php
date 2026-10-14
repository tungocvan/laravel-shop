<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\PriceListItem;
use Modules\Website\Services\PharmaQuickOrderSettingsService;
use Modules\Pharma\Services\UserOrderAuthoringService;
use Modules\Website\Models\PharmaQuickOrderRequest;

final class PharmaQuickOrderIssueHandoffService
{
    public function __construct(
        private readonly PharmaQuickOrderIssueReadinessService $readiness,
        private readonly UserOrderAuthoringService $authoring,
        private readonly PharmaQuickOrderSettingsService $settings,
    ) {}

    /**
     * One transaction: verify pending request, resolve a unique configured price list
     * and its exact product variants, create draft, then accept and audit.
     */
    public function acceptAndCreateDraft(int $requestId, int $adminId, int $managerUserId, int $partnerId, string $note): InventoryIssue
    {
        return DB::transaction(function () use ($requestId, $adminId, $managerUserId, $partnerId, $note): InventoryIssue {
            $request = PharmaQuickOrderRequest::query()->lockForUpdate()->findOrFail($requestId);
            if ($request->inventory_issue_id) {
                return InventoryIssue::query()->findOrFail($request->inventory_issue_id);
            }
            if ($request->status !== PharmaQuickOrderRequest::PENDING_REVIEW) {
                throw ValidationException::withMessages(['handoff' => 'Chỉ có thể duyệt và lập phiếu cho yêu cầu đang chờ xét duyệt.']);
            }

            $inspection = $this->readiness->inspect($request, true);
            if (! $inspection['ready_for_authoring']) {
                throw ValidationException::withMessages(['handoff' => implode(' ', $inspection['problems'])]);
            }

            $settings = $this->settings->get();
            if (! $settings['enabled'] || empty($settings['price_list_ids'])) {
                throw ValidationException::withMessages(['handoff' => 'Chưa bật đặt hàng nhanh hoặc chưa cấu hình bảng giá.']);
            }
            if (! Partner::query()->withPartnerType('customer')->whereKey($partnerId)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['recipient_partner_id' => 'Khách hàng Pharma không còn hợp lệ hoặc chưa hoạt động.']);
            }
            $date = now()->toDateString();
            $candidates = $this->authoring->priceLists($managerUserId, $date)
                ->filter(fn ($list) => in_array((int) $list->id, $settings['price_list_ids'], true))
                ->filter(fn ($list) => ! $list->partner_id || (int) $list->partner_id === $partnerId);

            $matches = collect();
            foreach ($candidates as $list) {
                $quantities = [];
                foreach ($inspection['lines'] as $line) {
                    $eligible = $list->items->filter(fn ($item) =>
                        (int) $item->medicine_id === (int) $line['medicine_id']
                        && (int) $item->medicine_variant_id === (int) $line['medicine_variant_id']
                        && (int) $item->medicine_package_id === (int) $line['medicine_package_id']
                    );
                    if ($eligible->count() !== 1) {
                        $quantities = [];
                        break;
                    }
                    $itemId = (int) $eligible->first()->id;
                    if (isset($quantities[$itemId])) {
                        $quantities[$itemId] += (int) $line['quantity'];
                    } else {
                        $quantities[$itemId] = (int) $line['quantity'];
                    }
                }
                if ($quantities && count($quantities) > 0) {
                    $matches->push(['list' => $list, 'quantities' => $quantities]);
                }
            }
            if ($matches->count() !== 1) {
                throw ValidationException::withMessages(['handoff' => $matches->isEmpty()
                    ? 'Không có bảng giá đã cấu hình khớp đầy đủ thuốc và quy cách.'
                    : 'Có nhiều bảng giá cùng khớp. Vui lòng điều chỉnh cấu hình để chỉ còn một nguồn giá hợp lệ.']);
            }
            $match = $matches->first();
            $issue = $this->authoring->createDraft($managerUserId, $managerUserId, [
                'source' => 'price_list',
                'issue_date' => $date,
                'price_list_id' => (int) $match['list']->id,
                'recipient_partner_id' => $partnerId,
                'quantities' => $match['quantities'],
                'notes' => 'Yêu cầu Website #'.$request->id.' — '.$note,
            ]);
            $request->forceFill([
                'status' => 'accepted',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'review_note' => $note,
                'inventory_issue_id' => $issue->id,
            ])->save();
            DB::table('website_pharma_quick_order_reviews')->insert([
                'quick_order_request_id' => $request->id,
                'admin_id' => $adminId,
                'from_status' => PharmaQuickOrderRequest::PENDING_REVIEW,
                'to_status' => 'accepted',
                'note' => $note,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $issue;
        });
    }

    public function createDraft(int $requestId, int $managerUserId, int $partnerId, int $priceListId, array $selectedItems): InventoryIssue
    {
        return DB::transaction(function () use ($requestId, $managerUserId, $partnerId, $priceListId, $selectedItems): InventoryIssue {
            $request = PharmaQuickOrderRequest::query()->lockForUpdate()->findOrFail($requestId);

            if ($request->inventory_issue_id) {
                return InventoryIssue::query()->findOrFail($request->inventory_issue_id);
            }
            if ($request->status !== 'accepted') {
                throw ValidationException::withMessages(['handoff' => 'Chỉ được lập phiếu từ yêu cầu đã chấp nhận.']);
            }

            $settings = $this->settings->get();
            if (! $settings['enabled'] || ! in_array($priceListId, $settings['price_list_ids'], true)) {
                throw ValidationException::withMessages(['price_list_id' => 'Bảng giá không được phép sử dụng cho Đặt hàng nhanh.']);
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
