<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;
use Modules\Pharma\Models\DrugBidAwardProductPolicy;

class DrugBidAwardCommercialPolicyService
{
    public function __construct(private readonly DrugBidAwardResultGroupService $groups) {}

    public function saveProductPolicies(DrugBidAward $contextAward, array $percentages, ?int $actorId): void
    {
        $validIds = $this->groups->awardsQuery($contextAward)->pluck('id')->map(fn ($id) => (int) $id)->all();

        DB::transaction(function () use ($percentages, $validIds, $actorId) {
            foreach ($percentages as $awardId => $percentage) {
                $awardId = (int) $awardId;
                if (! in_array($awardId, $validIds, true)) {
                    throw ValidationException::withMessages(['productPolicies' => 'Có sản phẩm không thuộc TBMT hiện tại.']);
                }
                if ($percentage === '' || $percentage === null) continue;
                $value = (float) $percentage;
                if ($value < 0 || $value > 100) {
                    throw ValidationException::withMessages(["productPolicies.$awardId" => 'Chính sách % phải từ 0 đến 100.']);
                }
                $policy = DrugBidAwardProductPolicy::query()->firstOrNew(['drug_bid_award_id' => $awardId]);
                if (! $policy->exists) $policy->created_by = $actorId;
                $policy->commission_percentage = $value;
                $policy->updated_by = $actorId;
                $policy->save();
            }
        }, 3);
    }

    public function assignManager(DrugBidAward $contextAward, int $awardId, int $partnerId, int $userId, ?int $actorId): void
    {
        $validAward = $this->groups->awardsQuery($contextAward)->whereKey($awardId)->exists();
        if (! $validAward) {
            throw ValidationException::withMessages(['assignment' => 'Sản phẩm không thuộc TBMT hiện tại.']);
        }

        $allocated = DrugBidAwardAllocation::query()
            ->where('drug_bid_award_id', $awardId)
            ->where('partner_id', $partnerId)
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->exists();

        if (! $allocated) {
            throw ValidationException::withMessages(['assignment' => 'Sản phẩm chưa được phân bổ cho bệnh viện đã chọn.']);
        }

        DrugBidAwardManagementAssignment::query()->updateOrCreate(
            ['drug_bid_award_id' => $awardId, 'partner_id' => $partnerId],
            ['user_id' => $userId, 'status' => DrugBidAwardManagementAssignment::STATUS_ACTIVE, 'updated_by' => $actorId, 'created_by' => $actorId]
        );
    }

    public function removeManager(DrugBidAward $contextAward, int $assignmentId): void
    {
        $validIds = $this->groups->awardsQuery($contextAward)->pluck('id');
        DrugBidAwardManagementAssignment::query()->whereKey($assignmentId)->whereIn('drug_bid_award_id', $validIds)->delete();
    }
}
