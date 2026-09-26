<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardContract;
use Modules\Pharma\Models\DrugBidAwardDistributionScope;

class DrugBidAwardAllocationService
{
    public function save(int $awardId, ?int $allocationId, array $data, ?int $adminId): DrugBidAwardAllocation
    {
        return DB::transaction(function () use ($awardId, $allocationId, $data, $adminId) {
            $award = DrugBidAward::query()->lockForUpdate()->findOrFail($awardId);
            $partnerId = (int) $data['partner_id'];
            $partner = Partner::query()->findOrFail($partnerId);

            if ($partner->legal_type !== 'hospital' || $partner->status !== 'active') {
                throw ValidationException::withMessages([
                    'partner_id' => 'Chỉ được phân bổ cho bệnh viện đang hoạt động trong Partner Master.',
                ]);
            }

            $scopeKey = $award->bidding_notice_code ? 'tbmt:'.trim($award->bidding_notice_code) : 'award:'.$award->id;
            $scope = DrugBidAwardDistributionScope::query()->where('result_key', $scopeKey)->first();
            if (! $scope || ! $scope->partners()->whereKey($partnerId)->exists()) {
                throw ValidationException::withMessages([
                    'partner_id' => 'Bệnh viện chưa nằm trong phạm vi phân bổ đã thiết lập cho kết quả trúng thầu.',
                ]);
            }

            $allocation = $allocationId
                ? DrugBidAwardAllocation::query()->where('drug_bid_award_id', $award->id)->findOrFail($allocationId)
                : DrugBidAwardAllocation::query()
                    ->where('drug_bid_award_id', $award->id)
                    ->where('partner_id', $partnerId)
                    ->first() ?? new DrugBidAwardAllocation(['drug_bid_award_id' => $award->id]);

            if ($allocation->exists && (int) $allocation->partner_id !== $partnerId) {
                $duplicate = DrugBidAwardAllocation::query()
                    ->where('drug_bid_award_id', $award->id)
                    ->where('partner_id', $partnerId)
                    ->where('id', '!=', $allocation->id)
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'partner_id' => 'Bệnh viện đã có phân bổ cho sản phẩm trúng thầu này.',
                    ]);
                }
            }

            $quantity = round((float) $data['allocated_quantity'], 4);
            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'allocated_quantity' => 'Số lượng phân bổ phải lớn hơn 0.',
                ]);
            }

            $otherAllocated = (float) DrugBidAwardAllocation::query()
                ->where('drug_bid_award_id', $award->id)
                ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
                ->when($allocation->exists, fn ($query) => $query->where('id', '!=', $allocation->id))
                ->sum('allocated_quantity');

            if (($otherAllocated + $quantity) > ((float) $award->quantity + 0.00005)) {
                throw ValidationException::withMessages([
                    'allocated_quantity' => 'Tổng phân bổ không được vượt số lượng trúng thầu còn lại.',
                ]);
            }

            $committed = $allocation->exists
                ? (float) DrugBidAwardContract::query()
                    ->where('drug_bid_award_allocation_id', $allocation->id)
                    ->whereIn('status', DrugBidAwardContract::COMMITTED_STATUSES)
                    ->sum('contract_quantity')
                : 0.0;

            if ($quantity + 0.00005 < $committed) {
                throw ValidationException::withMessages([
                    'allocated_quantity' => 'Không thể giảm phân bổ thấp hơn số lượng hợp đồng đã cam kết.',
                ]);
            }

            $allocation->fill([
                'partner_id' => $partnerId,
                'allocated_quantity' => $quantity,
                'status' => DrugBidAwardAllocation::STATUS_ACTIVE,
                'effective_from' => $scope->effective_from,
                'effective_until' => $scope->effective_until,
                'notes' => $data['notes'] ?? null,
                'updated_by' => $adminId,
            ]);

            if (! $allocation->exists) {
                $allocation->created_by = $adminId;
            }

            $allocation->cancelled_by = null;
            $allocation->cancelled_at = null;
            $allocation->cancellation_reason = null;
            $allocation->save();

            return $allocation->fresh(['partner', 'contracts']);
        }, 3);
    }

    public function cancel(int $awardId, int $allocationId, string $reason, ?int $adminId): void
    {
        DB::transaction(function () use ($awardId, $allocationId, $reason, $adminId) {
            DrugBidAward::query()->lockForUpdate()->findOrFail($awardId);
            $allocation = DrugBidAwardAllocation::query()
                ->where('drug_bid_award_id', $awardId)
                ->findOrFail($allocationId);

            $committed = (float) $allocation->contracts()
                ->whereIn('status', DrugBidAwardContract::COMMITTED_STATUSES)
                ->sum('contract_quantity');

            if ($committed > 0.00005) {
                throw ValidationException::withMessages([
                    'allocation' => 'Không thể hủy phân bổ đang có hợp đồng hiệu lực/cam kết.',
                ]);
            }

            $allocation->update([
                'status' => DrugBidAwardAllocation::STATUS_CANCELLED,
                'cancelled_by' => $adminId,
                'cancelled_at' => now(),
                'cancellation_reason' => trim($reason),
                'updated_by' => $adminId,
            ]);
        }, 3);
    }
}
