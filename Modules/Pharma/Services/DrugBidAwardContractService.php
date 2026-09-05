<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardContract;

class DrugBidAwardContractService
{
    public function save(int $allocationId, ?int $contractId, array $data, ?int $adminId): DrugBidAwardContract
    {
        return DB::transaction(function () use ($allocationId, $contractId, $data, $adminId) {
            $allocation = DrugBidAwardAllocation::query()->lockForUpdate()->findOrFail($allocationId);

            if (! $allocation->isActive()) {
                throw ValidationException::withMessages(['contract' => 'Không thể quản lý hợp đồng trên phân bổ đã hủy.']);
            }

            $contract = $contractId
                ? DrugBidAwardContract::query()->where('drug_bid_award_allocation_id', $allocation->id)->findOrFail($contractId)
                : new DrugBidAwardContract(['drug_bid_award_allocation_id' => $allocation->id]);

            $status = (string) $data['status'];
            $allowed = [
                DrugBidAwardContract::STATUS_DRAFT,
                DrugBidAwardContract::STATUS_SIGNED,
                DrugBidAwardContract::STATUS_IN_PROGRESS,
                DrugBidAwardContract::STATUS_COMPLETED,
            ];
            if (! in_array($status, $allowed, true)) {
                throw ValidationException::withMessages(['contract_status' => 'Trạng thái hợp đồng không hợp lệ.']);
            }

            $quantity = round((float) $data['contract_quantity'], 4);
            if ($quantity <= 0) {
                throw ValidationException::withMessages(['contract_quantity' => 'Số lượng hợp đồng phải lớn hơn 0.']);
            }

            $otherCommitted = (float) DrugBidAwardContract::query()
                ->where('drug_bid_award_allocation_id', $allocation->id)
                ->whereIn('status', DrugBidAwardContract::COMMITTED_STATUSES)
                ->when($contract->exists, fn ($query) => $query->whereKeyNot($contract->id))
                ->sum('contract_quantity');
            $candidateCommitted = in_array($status, DrugBidAwardContract::COMMITTED_STATUSES, true) ? $quantity : 0.0;

            if (($otherCommitted + $candidateCommitted) > ((float) $allocation->allocated_quantity + 0.00005)) {
                throw ValidationException::withMessages(['contract_quantity' => 'Tổng số lượng hợp đồng hiệu lực không được vượt số lượng đã phân bổ.']);
            }

            $contract->fill([
                'contract_number' => trim((string) $data['contract_number']),
                'contract_date' => $data['contract_date'] ?? null,
                'contract_quantity' => $quantity,
                'contract_value' => $data['contract_value'] !== null && $data['contract_value'] !== '' ? $data['contract_value'] : null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $status,
                'notes' => $data['contract_notes'] ?? null,
                'updated_by' => $adminId,
            ]);

            if (! $contract->exists) {
                $contract->created_by = $adminId;
            }

            $contract->save();

            return $contract->fresh();
        }, 3);
    }

    public function cancel(int $allocationId, int $contractId, string $reason, ?int $adminId): void
    {
        DB::transaction(function () use ($allocationId, $contractId, $reason, $adminId) {
            DrugBidAwardAllocation::query()->lockForUpdate()->findOrFail($allocationId);
            $contract = DrugBidAwardContract::query()->where('drug_bid_award_allocation_id', $allocationId)->findOrFail($contractId);
            $contract->update([
                'status' => DrugBidAwardContract::STATUS_CANCELLED,
                'cancelled_by' => $adminId,
                'cancelled_at' => now(),
                'cancellation_reason' => trim($reason),
                'updated_by' => $adminId,
            ]);
        }, 3);
    }
}
