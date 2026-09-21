<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardCommercialAssignment;
use Modules\Pharma\Models\DrugBidAwardCommercialPolicy;

class DrugBidAwardCommercialPolicyService
{
    public function __construct(private readonly DrugBidAwardResultGroupService $groups) {}

    public function currentForAward(DrugBidAward $award): ?DrugBidAwardCommercialPolicy
    {
        return DrugBidAwardCommercialPolicy::query()->with(['assignments.user'])
            ->where('result_key', $this->groups->resultKey($award))
            ->whereIn('status', [DrugBidAwardCommercialPolicy::STATUS_DRAFT, DrugBidAwardCommercialPolicy::STATUS_ACTIVE])
            ->latest('id')->first();
    }

    public function saveDraft(DrugBidAward $award, array $data, ?int $actorId): DrugBidAwardCommercialPolicy
    {
        if (! app(DrugBidAwardDistributionScopeService::class)->findForAward($award)) {
            throw ValidationException::withMessages(['policy' => 'Hãy thiết lập phạm vi phân bổ trước khi tạo chính sách kinh doanh.']);
        }
        $awardIds = $this->groups->awardsQuery($award)->pluck('id');
        $hasAllocation = DrugBidAwardAllocation::query()->whereIn('drug_bid_award_id', $awardIds)->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)->exists();
        if (! $hasAllocation) throw ValidationException::withMessages(['policy' => 'Cần có ít nhất một phân bổ hiệu lực trước khi tạo chính sách kinh doanh.']);

        return DB::transaction(function () use ($award, $data, $actorId) {
            $policy = $this->currentForAward($award);
            if ($policy?->status === DrugBidAwardCommercialPolicy::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['policy' => 'Chính sách đã kích hoạt. Hãy lưu trữ chính sách hiện tại trước khi tạo phiên bản mới.']);
            }
            $policy ??= new DrugBidAwardCommercialPolicy([
                'result_key' => $this->groups->resultKey($award),
                'bidding_notice_code' => $award->bidding_notice_code,
                'status' => DrugBidAwardCommercialPolicy::STATUS_DRAFT,
                'created_by' => $actorId,
            ]);
            $policy->fill($data + ['updated_by' => $actorId]);
            $policy->save();
            return $policy->refresh();
        }, 3);
    }

    public function assign(DrugBidAward $contextAward, DrugBidAwardCommercialPolicy $policy, array $awardIds, int $userId, float $share, string $from, ?string $until, ?int $actorId): void
    {
        if ($policy->result_key !== $this->groups->resultKey($contextAward)) throw ValidationException::withMessages(['assignment' => 'Chính sách không thuộc kết quả trúng thầu hiện tại.']);
        if ($policy->status !== DrugBidAwardCommercialPolicy::STATUS_DRAFT) throw ValidationException::withMessages(['assignment' => 'Chỉ được chỉnh phân công khi chính sách ở trạng thái Draft.']);
        if ($share <= 0 || $share > 100) throw ValidationException::withMessages(['sharePercentage' => 'Tỷ lệ phải lớn hơn 0 và không vượt 100%.']);
        $requested = array_values(array_unique(array_map('intval', $awardIds)));
        $validIds = $this->groups->awardsQuery($contextAward)->whereIn('id', $requested)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($requested === [] || count($validIds) !== count($requested)) throw ValidationException::withMessages(['selectedAwardIds' => 'Có sản phẩm không thuộc kết quả trúng thầu hiện tại.']);

        DB::transaction(function () use ($policy, $validIds, $userId, $share, $from, $until, $actorId) {
            foreach ($validIds as $awardId) {
                DrugBidAwardCommercialAssignment::query()->create([
                    'commercial_policy_id' => $policy->id, 'drug_bid_award_id' => $awardId, 'user_id' => $userId,
                    'share_percentage' => $share, 'effective_from' => $from, 'effective_until' => $until,
                    'status' => DrugBidAwardCommercialAssignment::STATUS_ACTIVE, 'created_by' => $actorId, 'updated_by' => $actorId,
                ]);
            }
        }, 3);
    }

    public function endAssignment(DrugBidAwardCommercialAssignment $assignment, string $reason, ?int $actorId): void
    {
        if ($assignment->policy->status !== DrugBidAwardCommercialPolicy::STATUS_DRAFT) throw ValidationException::withMessages(['assignment' => 'Chỉ được thay đổi phân công khi chính sách ở trạng thái Draft.']);
        $assignment->update(['status' => DrugBidAwardCommercialAssignment::STATUS_ENDED, 'effective_until' => today(), 'ended_by' => $actorId, 'ended_at' => now(), 'end_reason' => trim($reason), 'updated_by' => $actorId]);
    }

    public function activationIssues(DrugBidAward $award, DrugBidAwardCommercialPolicy $policy): array
    {
        $issues = [];
        if ($policy->result_key !== $this->groups->resultKey($award)) return ['Chính sách không thuộc kết quả trúng thầu hiện tại.'];
        if (! app(DrugBidAwardDistributionScopeService::class)->findForAward($award)) $issues[] = 'Chưa thiết lập phạm vi phân bổ cho TBMT.';
        $awards = $this->groups->awardsQuery($award)->with(['allocations' => fn ($q) => $q->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)])->get();
        $assignments = $policy->assignments()->where('status', DrugBidAwardCommercialAssignment::STATUS_ACTIVE)->get()->groupBy('drug_bid_award_id');
        foreach ($awards as $item) {
            $allocated = (float) $item->allocations->sum('allocated_quantity');
            $winning = (float) ($item->quantity ?? 0);
            $label = $item->medicine_name ?: 'Sản phẩm #'.$item->id;
            if ($item->allocations->isEmpty()) $issues[] = $label.': chưa có phân bổ hiệu lực.';
            if ($allocated > $winning + 0.0001) $issues[] = $label.': tổng phân bổ vượt số lượng trúng thầu.';
            $rows = $assignments->get($item->id, collect());
            if ($rows->isEmpty()) $issues[] = $label.': chưa có User phụ trách.';
            elseif (abs((float) $rows->sum('share_percentage') - 100.0) > 0.0001) $issues[] = $label.': tổng tỷ lệ User phải bằng 100%.';
        }
        return $issues;
    }

    public function activate(DrugBidAward $award, DrugBidAwardCommercialPolicy $policy, ?int $actorId): void
    {
        $issues = $this->activationIssues($award, $policy);
        if ($issues !== []) throw ValidationException::withMessages(['activation' => $issues]);
        $policy->update(['status' => DrugBidAwardCommercialPolicy::STATUS_ACTIVE, 'activated_by' => $actorId, 'activated_at' => now(), 'updated_by' => $actorId]);
    }

    public function archive(DrugBidAward $award, DrugBidAwardCommercialPolicy $policy, ?int $actorId): void
    {
        if ($policy->result_key !== $this->groups->resultKey($award)) throw ValidationException::withMessages(['policy' => 'Chính sách không thuộc kết quả trúng thầu hiện tại.']);
        if ($policy->status !== DrugBidAwardCommercialPolicy::STATUS_ACTIVE) throw ValidationException::withMessages(['policy' => 'Chỉ chính sách đang Active mới được lưu trữ.']);
        $policy->update(['status' => DrugBidAwardCommercialPolicy::STATUS_ARCHIVED, 'archived_by' => $actorId, 'archived_at' => now(), 'updated_by' => $actorId]);
    }
}
