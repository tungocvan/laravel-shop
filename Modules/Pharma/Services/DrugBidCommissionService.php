<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;
use Modules\Pharma\Models\DrugBidAwardProductPolicy;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryIssueCommission;
use Modules\Pharma\Models\PriceListItem;

final class DrugBidCommissionService
{
    public function snapshotPostedIssue(InventoryIssue $issue, ?int $actorId): void
    {
        if ($issue->status !== InventoryIssue::POSTED) return;
        if (($issue->issue_source ?? 'normal') !== 'bid') {
            $this->snapshotPriceListIssue($issue,$actorId);
            return;
        }

        DB::transaction(function () use ($issue,$actorId): void {
            $issue=InventoryIssue::query()->lockForUpdate()->findOrFail($issue->id);
            $issue->load(['items','deferredSupplies']);
            $deferredMedicineIds=$issue->deferredSupplies->pluck('medicine_id')->map(fn($id)=>(int)$id)->unique();
            $postedItems=$issue->items->reject(fn($item)=>$deferredMedicineIds->contains((int)$item->medicine_id));

            foreach ($postedItems as $item) {
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
                        'source_type'=>InventoryIssueCommission::SOURCE_BID,'resolution_note'=>$note,'calculated_at'=>$issue->posted_at ?? now(),'created_by'=>$actorId,
                    ]
                );
            }
        },3);
    }

    private function snapshotPriceListIssue(InventoryIssue $issue, ?int $actorId): void
    {
        if(!$issue->price_list_id || !$issue->manager_user_id) return;

        DB::transaction(function () use ($issue,$actorId): void {
            $issue=InventoryIssue::query()->lockForUpdate()->findOrFail($issue->id);
            $issue->load(['items','deferredSupplies']);
            $deferredMedicineIds=$issue->deferredSupplies->pluck('medicine_id')->map(fn($id)=>(int)$id)->unique();
            $postedItems=$issue->items->reject(fn($item)=>$deferredMedicineIds->contains((int)$item->medicine_id));
            $priceItems=PriceListItem::query()->where('price_list_id',$issue->price_list_id)
                ->whereIn('medicine_id',$postedItems->pluck('medicine_id')->unique())
                ->get()->groupBy('medicine_id');

            foreach($postedItems as $item){
                $priceItem=$priceItems->get($item->medicine_id)?->first();
                $sale=(float)$item->unit_price;
                $receivable=$priceItem?->actual_receivable_price !== null ? (float)$priceItem->actual_receivable_price : null;
                $commission=$receivable !== null ? max(0,round(((float)$item->quantity)*($sale-$receivable),2)) : 0;
                $percentage=($receivable !== null && $sale>0) ? round((($sale-$receivable)/$sale)*100,4) : null;
                InventoryIssueCommission::query()->updateOrCreate(
                    ['issue_item_id'=>$item->id,'entry_type'=>InventoryIssueCommission::TYPE_EARNED],
                    [
                        'issue_id'=>$issue->id,'partner_id'=>$issue->recipient_partner_id,'medicine_id'=>$item->medicine_id,
                        'user_id'=>$issue->manager_user_id,'quantity'=>$item->quantity,'unit_price'=>$sale,
                        'revenue_amount'=>round((float)$item->quantity*$sale,2),'commission_percentage'=>$percentage,
                        'commission_amount'=>$commission,'status'=>$receivable !== null ? InventoryIssueCommission::STATUS_EARNED : InventoryIssueCommission::STATUS_UNRESOLVED,
                        'resolution_note'=>$receivable !== null ? null : 'Bảng giá chưa có Giá thu cho sản phẩm tại thời điểm ghi sổ.',
                        'calculated_at'=>$issue->posted_at ?? now(),'created_by'=>$actorId,
                        'source_type'=>InventoryIssueCommission::SOURCE_PRICE_LIST,'price_list_id'=>$issue->price_list_id,
                        'price_list_item_id'=>$priceItem?->id,'sale_price_snapshot'=>$sale,'receivable_price_snapshot'=>$receivable,
                    ]
                );
            }
        },3);
    }

    public function reverseIssue(InventoryIssue $issue, ?int $actorId): void
    {
        DB::transaction(function () use ($issue): void {
            InventoryIssueCommission::query()
                ->where('issue_id',$issue->id)
                ->lockForUpdate()
                ->delete();
        },3);
    }
}
