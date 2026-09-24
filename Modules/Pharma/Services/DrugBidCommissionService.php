<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;
use Modules\Pharma\Models\DrugBidAwardProductPolicy;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryIssueCommission;

final class DrugBidCommissionService
{
    public function snapshotPostedIssue(InventoryIssue $issue, ?int $actorId): void
    {
        if (($issue->issue_source ?? 'normal') !== 'bid' || $issue->status !== InventoryIssue::POSTED) return;

        DB::transaction(function () use ($issue,$actorId): void {
            $issue=InventoryIssue::query()->lockForUpdate()->findOrFail($issue->id);
            $issue->load('items');

            foreach ($issue->items as $item) {
                $assignment=DrugBidAwardManagementAssignment::query()
                    ->where('drug_bid_award_id',$item->drug_bid_award_id)
                    ->where('partner_id',$issue->bid_partner_id)
                    ->where('status',DrugBidAwardManagementAssignment::STATUS_ACTIVE)
                    ->first();
                $policy=DrugBidAwardProductPolicy::query()->where('drug_bid_award_id',$item->drug_bid_award_id)->first();
                $revenue=round((float)$item->quantity*(float)$item->unit_price,2);
                $percentage=$policy?->commission_percentage !== null ? (float)$policy->commission_percentage : null;
                $resolved=$assignment && $percentage !== null;
                $note=!$assignment ? 'Chưa phân công User phụ trách cho Bệnh viện × Sản phẩm tại thời điểm ghi sổ.'
                    : ($percentage === null ? 'Chưa thiết lập chính sách % hoa hồng cho sản phẩm tại thời điểm ghi sổ.' : null);

                InventoryIssueCommission::query()->updateOrCreate(
                    ['issue_item_id'=>$item->id,'entry_type'=>InventoryIssueCommission::TYPE_EARNED],
                    [
                        'issue_id'=>$issue->id,'drug_bid_award_id'=>$item->drug_bid_award_id,
                        'drug_bid_award_allocation_id'=>$item->drug_bid_award_allocation_id,'partner_id'=>$issue->bid_partner_id,
                        'medicine_id'=>$item->medicine_id,'user_id'=>$assignment?->user_id,'quantity'=>$item->quantity,
                        'unit_price'=>$item->unit_price,'revenue_amount'=>$revenue,'commission_percentage'=>$percentage,
                        'commission_amount'=>$resolved ? round($revenue*$percentage/100,2) : 0,
                        'status'=>$resolved ? InventoryIssueCommission::STATUS_EARNED : InventoryIssueCommission::STATUS_UNRESOLVED,
                        'resolution_note'=>$note,'calculated_at'=>$issue->posted_at ?? now(),'created_by'=>$actorId,
                    ]
                );
            }
        },3);
    }

    public function reverseIssue(InventoryIssue $issue, ?int $actorId): void
    {
        if (($issue->issue_source ?? 'normal') !== 'bid') return;

        DB::transaction(function () use ($issue,$actorId): void {
            $earned=InventoryIssueCommission::query()
                ->where('issue_id',$issue->id)->where('entry_type',InventoryIssueCommission::TYPE_EARNED)
                ->whereIn('status',[InventoryIssueCommission::STATUS_EARNED,InventoryIssueCommission::STATUS_UNRESOLVED])
                ->lockForUpdate()->get();

            foreach($earned as $row){
                InventoryIssueCommission::query()->updateOrCreate(
                    ['issue_item_id'=>$row->issue_item_id,'entry_type'=>InventoryIssueCommission::TYPE_REVERSAL],
                    [
                        'issue_id'=>$row->issue_id,'original_commission_id'=>$row->id,'drug_bid_award_id'=>$row->drug_bid_award_id,
                        'drug_bid_award_allocation_id'=>$row->drug_bid_award_allocation_id,'partner_id'=>$row->partner_id,
                        'medicine_id'=>$row->medicine_id,'user_id'=>$row->user_id,'quantity'=>-$row->quantity,
                        'unit_price'=>$row->unit_price,'revenue_amount'=>-$row->revenue_amount,
                        'commission_percentage'=>$row->commission_percentage,'commission_amount'=>-$row->commission_amount,
                        'status'=>InventoryIssueCommission::STATUS_REVERSED,'resolution_note'=>'Hoàn tác theo phiếu xuất kho.',
                        'calculated_at'=>now(),'created_by'=>$actorId,
                    ]
                );
                $row->update(['status'=>InventoryIssueCommission::STATUS_REVERSED]);
            }
        },3);
    }
}
