<?php

namespace Modules\Pharma\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\PriceListItem;

final class UserOrderAuthoringService
{
    public function orderManagers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereExists(fn ($sub) => $sub->selectRaw('1')->from('pharma_price_lists as pl')
                    ->whereColumn('pl.manager_user_id', 'users.id')->where('pl.status', PriceList::STATUS_ACTIVE))
                    ->orWhereExists(fn ($sub) => $sub->selectRaw('1')->from('pharma_price_list_users as plu')
                        ->join('pharma_price_lists as pl', 'pl.id', '=', 'plu.price_list_id')
                        ->whereColumn('plu.user_id', 'users.id')->where('pl.status', PriceList::STATUS_ACTIVE))
                    ->orWhereExists(fn ($sub) => $sub->selectRaw('1')->from('pharma_drug_bid_award_management_assignments as a')
                        ->whereColumn('a.user_id', 'users.id')->where('a.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE));
            })
            ->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function priceLists(int $userId, string $date): Collection
    {
        return PriceList::query()
            ->with(['partner:id,name', 'items' => fn ($query) => $query
                ->with('medicine:id,name,medicine_code,unit')
                ->where('status', 'active')
                ->whereNotNull('medicine_id')
                ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
                ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))])
            ->whereIn('type', [PriceList::TYPE_GLOBAL, PriceList::TYPE_CUSTOMER])
            ->activeAt($date)
            ->where(function ($query) use ($userId): void {
                $query->where('manager_user_id', $userId)
                    ->orWhereHas('globalUsers', fn ($users) => $users->where('users.id', $userId));
            })
            ->orderByDesc('priority')->orderBy('name')->get();
    }

    public function customers(): Collection
    {
        return Partner::query()->withPartnerType('customer')->where('status', 'active')
            ->orderBy('name')->get(['id', 'name', 'tax_code']);
    }

    public function bidRows(int $userId, string $date): Collection
    {
        $allocations = DrugBidAwardAllocation::query()
            ->with(['partner:id,name', 'award.medicine:id,name,medicine_code,unit'])
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))
            ->whereExists(fn ($q) => $q->selectRaw('1')
                ->from('pharma_drug_bid_award_management_assignments as a')
                ->whereColumn('a.drug_bid_award_id', 'pharma_drug_bid_award_allocations.drug_bid_award_id')
                ->whereColumn('a.partner_id', 'pharma_drug_bid_award_allocations.partner_id')
                ->where('a.user_id', $userId)
                ->where('a.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE))
            ->get();

        $posted = DB::table('pharma_inventory_issue_items as ii')
            ->join('pharma_inventory_issues as i', 'i.id', '=', 'ii.issue_id')
            ->where('i.issue_source', 'bid')->where('i.status', InventoryIssue::POSTED)
            ->whereIn('ii.drug_bid_award_allocation_id', $allocations->pluck('id'))
            ->groupBy('ii.drug_bid_award_allocation_id')
            ->selectRaw('ii.drug_bid_award_allocation_id, SUM(ii.quantity) qty')
            ->pluck('qty', 'ii.drug_bid_award_allocation_id');

        return $allocations->map(function ($allocation) use ($posted) {
            $award = $allocation->award;
            $remaining = max(0, (float) $allocation->allocated_quantity - (float) ($posted[$allocation->id] ?? 0));

            return (object) [
                'allocation_id' => (int) $allocation->id,
                'partner_id' => (int) $allocation->partner_id,
                'partner_name' => $allocation->partner?->name,
                'award_id' => (int) $award->id,
                'medicine_id' => (int) $award->medicine_id,
                'medicine_name' => $award->medicine?->name ?: $award->medicine_name,
                'medicine_code' => $award->medicine?->medicine_code ?: $award->medicine_code,
                'unit' => $award->medicine?->unit ?: $award->unit,
                'investor_code' => $award->investor_code,
                'investor_name' => $award->investor_name,
                'allocated_quantity' => (float) $allocation->allocated_quantity,
                'remaining_quantity' => $remaining,
                'unit_price' => (float) ($award->winning_price ?? $award->unit_price ?? 0),
            ];
        })->filter(fn ($row) => $row->medicine_id > 0 && $row->remaining_quantity > 0)->values();
    }

    public function createDraft(int $actorUserId, int $managerUserId, array $data): InventoryIssue
    {
        return DB::transaction(function () use ($actorUserId, $managerUserId, $data): InventoryIssue {
            $source = $data['source'];
            $date = $data['issue_date'];
            $items = $source === 'bid'
                ? $this->resolveBidItems($managerUserId, $date, $data)
                : $this->resolvePriceListItems($managerUserId, $date, $data);

            $partner = Partner::query()->whereKey($items['partner_id'])->where('status', 'active')->firstOrFail();
            $warehouse = app(InventoryService::class)->defaultWarehouse();
            $issue = InventoryIssue::create([
                'warehouse_id' => $warehouse->id,
                'number' => 'PX-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'issue_date' => $date,
                'recipient_name' => $partner->name,
                'recipient_partner_id' => $partner->id,
                'manager_user_id' => $managerUserId,
                'price_list_id' => $items['price_list_id'],
                'issue_source' => $source === 'bid' ? 'bid' : 'normal',
                'bid_partner_id' => $source === 'bid' ? $partner->id : null,
                'bid_investor_code' => $items['investor_code'],
                'bid_investor_name' => $items['investor_name'],
                'status' => InventoryIssue::DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorUserId,
            ]);
            $issue->items()->createMany($items['rows']);

            return $issue;
        });
    }

    public function updateDraft(int $actorUserId, int $managerUserId, InventoryIssue $issue, array $data): InventoryIssue
    {
        $this->guardEditable($actorUserId, $issue);
        if (($issue->issue_source === 'bid' ? 'bid' : 'price_list') !== $data['source']) {
            throw ValidationException::withMessages(['source' => 'Nguồn đơn hàng không được thay đổi sau khi tạo nháp.']);
        }

        return DB::transaction(function () use ($managerUserId, $issue, $data): InventoryIssue {
            $items = $data['source'] === 'bid'
                ? $this->resolveBidItems($managerUserId, $data['issue_date'], $data)
                : $this->resolvePriceListItems($managerUserId, $data['issue_date'], $data);
            $partner = Partner::query()->whereKey($items['partner_id'])->where('status', 'active')->firstOrFail();

            $issue->update([
                'issue_date' => $data['issue_date'], 'recipient_name' => $partner->name,
                'manager_user_id' => $managerUserId,
                'recipient_partner_id' => $partner->id, 'price_list_id' => $items['price_list_id'],
                'bid_partner_id' => $data['source'] === 'bid' ? $partner->id : null,
                'bid_investor_code' => $items['investor_code'],
                'bid_investor_name' => $items['investor_name'], 'notes' => $data['notes'] ?? null,
            ]);
            $issue->items()->delete();
            $issue->items()->createMany($items['rows']);

            return $issue->refresh();
        });
    }

    public function submit(int $userId, InventoryIssue $issue): InventoryIssue
    {
        $this->guardEditable($userId, $issue);
        if (! $issue->items()->exists()) {
            throw ValidationException::withMessages(['order' => 'Đơn hàng chưa có sản phẩm để gửi duyệt.']);
        }

        $issue->update(['status' => InventoryIssue::PENDING_APPROVAL, 'submitted_by' => $userId, 'submitted_at' => now()]);

        return $issue->refresh();
    }

    private function resolvePriceListItems(int $userId, string $date, array $data): array
    {
        $priceList = $this->priceLists($userId, $date)->firstWhere('id', (int) $data['price_list_id']);
        if (! $priceList) throw ValidationException::withMessages(['price_list_id' => 'Bảng giá không còn hiệu lực hoặc không thuộc phạm vi của bạn.']);

        $partnerId = $priceList->partner_id ?: (int) ($data['recipient_partner_id'] ?? 0);
        if ($partnerId <= 0) throw ValidationException::withMessages(['recipient_partner_id' => 'Vui lòng chọn khách hàng.']);
        if ($priceList->partner_id && (int) $priceList->partner_id !== $partnerId) {
            throw ValidationException::withMessages(['recipient_partner_id' => 'Bảng giá này chỉ áp dụng cho khách hàng đã liên kết.']);
        }

        $quantities = collect($data['quantities'] ?? [])->mapWithKeys(fn ($qty, $id) => [(int) $id => (float) $qty])->filter(fn ($qty) => $qty > 0);
        if ($quantities->isEmpty()) throw ValidationException::withMessages(['quantities' => 'Vui lòng nhập số lượng cho ít nhất một sản phẩm.']);

        $items = PriceListItem::query()->with('medicine')->where('price_list_id', $priceList->id)
            ->whereIn('id', $quantities->keys())->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))->get()->keyBy('id');
        if ($items->count() !== $quantities->count()) throw ValidationException::withMessages(['quantities' => 'Có sản phẩm không còn thuộc bảng giá hiệu lực.']);

        return [
            'partner_id' => $partnerId, 'price_list_id' => $priceList->id, 'investor_code' => null, 'investor_name' => null,
            'rows' => $quantities->map(function ($quantity, $itemId) use ($items) {
                $item = $items[$itemId];
                if (! $item->medicine_id) throw ValidationException::withMessages(['quantities' => 'Sản phẩm chưa liên kết thuốc canonical.']);
                return ['medicine_id' => $item->medicine_id, 'batch_number' => null, 'expiry_date' => null,
                    'quantity' => $quantity, 'unit_price' => (float) $item->company_sale_price];
            })->values()->all(),
        ];
    }

    private function resolveBidItems(int $userId, string $date, array $data): array
    {
        $available = $this->bidRows($userId, $date)->keyBy('allocation_id');
        $quantities = collect($data['quantities'] ?? [])->mapWithKeys(fn ($qty, $id) => [(int) $id => (float) $qty])->filter(fn ($qty) => $qty > 0);
        if ($quantities->isEmpty()) throw ValidationException::withMessages(['quantities' => 'Vui lòng nhập số lượng cho ít nhất một sản phẩm.']);

        $rows = $quantities->map(function ($quantity, $allocationId) use ($available) {
            $row = $available->get($allocationId);
            if (! $row) throw ValidationException::withMessages(['quantities' => 'Phân bổ hàng thầu không còn thuộc phạm vi của bạn.']);
            if ($quantity > $row->remaining_quantity + 0.00005) throw ValidationException::withMessages(['quantities' => "Số lượng vượt phân bổ còn lại của {$row->medicine_name}."]);
            return $row;
        });
        if ($rows->pluck('partner_id')->unique()->count() !== 1) throw ValidationException::withMessages(['quantities' => 'Một đơn chỉ được thuộc một bệnh viện/khách hàng.']);
        $investorKeys = $rows->map(fn ($row) => $row->investor_code ?: $row->investor_name)->filter()->unique();
        if ($investorKeys->count() !== 1) throw ValidationException::withMessages(['quantities' => 'Một đơn chỉ được thuộc một chủ đầu tư.']);

        return [
            'partner_id' => (int) $rows->first()->partner_id, 'price_list_id' => null,
            'investor_code' => $rows->first()->investor_code,
            'investor_name' => $rows->first()->investor_name,
            'rows' => $rows->map(function ($row) use ($quantities) {
                return ['medicine_id' => $row->medicine_id, 'drug_bid_award_id' => $row->award_id,
                    'drug_bid_award_allocation_id' => $row->allocation_id, 'batch_number' => null, 'expiry_date' => null,
                    'quantity' => $quantities[$row->allocation_id], 'unit_price' => $row->unit_price];
            })->values()->all(),
        ];
    }

    private function guardEditable(int $userId, InventoryIssue $issue): void
    {
        if ((int) $issue->created_by !== $userId || $issue->status !== InventoryIssue::DRAFT) {
            throw ValidationException::withMessages(['order' => 'Chỉ người tạo mới được sửa hoặc gửi duyệt đơn đang ở trạng thái Nháp.']);
        }
    }
}
