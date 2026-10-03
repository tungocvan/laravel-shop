<?php

namespace Modules\Pharma\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryIssueCommission;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\CommissionQueryService;
use Modules\Pharma\Services\DrugBidCommissionService;
use Tests\TestCase;

final class CommissionLedgerIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reversal_is_append_only_idempotent_and_preserves_original_snapshot(): void
    {
        [$issue,$itemId,$medicineId]=$this->makeIssueGraph();

        $earned=InventoryIssueCommission::query()->create([
            'issue_id'=>$issue->id,'issue_item_id'=>$itemId,'medicine_id'=>$medicineId,'user_id'=>101,
            'quantity'=>2,'unit_price'=>500,'revenue_amount'=>1000,'commission_percentage'=>10,
            'commission_amount'=>100,'entry_type'=>InventoryIssueCommission::TYPE_EARNED,
            'status'=>InventoryIssueCommission::STATUS_EARNED,'source_type'=>InventoryIssueCommission::SOURCE_BID,
            'calculated_at'=>now(),'created_by'=>9,
        ]);

        $service=app(DrugBidCommissionService::class);
        $service->reverseIssue($issue,11);
        $service->reverseIssue($issue,11);

        $this->assertDatabaseHas('pharma_inventory_issue_commissions',[
            'id'=>$earned->id,'commission_amount'=>100,'status'=>InventoryIssueCommission::STATUS_REVERSED,
        ]);
        $this->assertDatabaseHas('pharma_inventory_issue_commissions',[
            'original_commission_id'=>$earned->id,'entry_type'=>InventoryIssueCommission::TYPE_REVERSAL,
            'commission_amount'=>-100,'revenue_amount'=>-1000,'created_by'=>11,
        ]);
        $this->assertSame(2,InventoryIssueCommission::query()->where('issue_id',$issue->id)->count());
        $this->assertEquals(0.0,(float)InventoryIssueCommission::query()->where('issue_id',$issue->id)->sum('commission_amount'));

        InventoryIssueCommission::query()->create([
            'issue_id'=>$issue->id,'issue_item_id'=>$itemId,'medicine_id'=>$medicineId,'user_id'=>101,
            'quantity'=>2,'unit_price'=>500,'revenue_amount'=>1000,'commission_percentage'=>15,
            'commission_amount'=>150,'entry_type'=>InventoryIssueCommission::TYPE_EARNED,
            'status'=>InventoryIssueCommission::STATUS_EARNED,'source_type'=>InventoryIssueCommission::SOURCE_BID,
            'calculated_at'=>now()->addSecond(),'created_by'=>12,
        ]);

        $this->assertSame(3,InventoryIssueCommission::query()->where('issue_id',$issue->id)->count());
        $this->assertEquals(150.0,(float)InventoryIssueCommission::query()->where('issue_id',$issue->id)->sum('commission_amount'));
        $this->assertDatabaseHas('pharma_inventory_issue_commissions',['id'=>$earned->id,'commission_percentage'=>10]);
    }

    public function test_user_query_applies_ownership_before_selected_ids_and_other_filters(): void
    {
        [$issue,$itemId,$medicineId]=$this->makeIssueGraph();

        $mine=InventoryIssueCommission::query()->create([
            'issue_id'=>$issue->id,'issue_item_id'=>$itemId,'medicine_id'=>$medicineId,'user_id'=>201,
            'quantity'=>1,'unit_price'=>100,'revenue_amount'=>100,'commission_percentage'=>10,'commission_amount'=>10,
            'entry_type'=>InventoryIssueCommission::TYPE_EARNED,'status'=>InventoryIssueCommission::STATUS_EARNED,
            'source_type'=>InventoryIssueCommission::SOURCE_BID,'calculated_at'=>now(),
        ]);
        $other=InventoryIssueCommission::query()->create([
            'issue_id'=>$issue->id,'issue_item_id'=>$itemId,'medicine_id'=>$medicineId,'user_id'=>202,
            'quantity'=>1,'unit_price'=>100,'revenue_amount'=>100,'commission_percentage'=>20,'commission_amount'=>20,
            'entry_type'=>InventoryIssueCommission::TYPE_EARNED,'status'=>InventoryIssueCommission::STATUS_EARNED,
            'source_type'=>InventoryIssueCommission::SOURCE_BID,'calculated_at'=>now(),
        ]);

        $ids=app(CommissionQueryService::class)->userQuery(201,['ids'=>[$mine->id,$other->id]])->pluck('id')->all();

        $this->assertSame([$mine->id],$ids);
        $this->assertFalse(in_array($other->id,$ids,true));
    }

    private function makeIssueGraph(): array
    {
        $suffix=bin2hex(random_bytes(4));
        $medicine=Medicine::query()->create([
            'active_ingredients'=>'Test','concentration'=>'1','name'=>'Commission '.$suffix,
            'dosage_form'=>'Tablet','route_of_administration'=>'Oral','unit'=>'Box',
            'packaging_specification'=>'Pack '.$suffix,'registration_number'=>'REG-'.$suffix,
            'shelf_life'=>'24 months','registered_company'=>'Test','manufacturing_company'=>'Test',
            'manufacturing_country'=>'VN',
        ]);
        $warehouseId=\DB::table('pharma_inventory_warehouses')->insertGetId([
            'code'=>'CW-'.$suffix,'name'=>'Commission warehouse '.$suffix,'is_active'=>1,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $issue=InventoryIssue::query()->create([
            'warehouse_id'=>$warehouseId,'number'=>'CI-'.$suffix,'issue_date'=>now()->toDateString(),
            'status'=>InventoryIssue::POSTED,'posted_at'=>now(),
        ]);
        $itemId=\DB::table('pharma_inventory_issue_items')->insertGetId([
            'issue_id'=>$issue->id,'medicine_id'=>$medicine->id,'batch_number'=>'B-'.$suffix,
            'expiry_date'=>now()->addYear()->toDateString(),'quantity'=>2,'unit_price'=>500,
            'created_at'=>now(),'updated_at'=>now(),
        ]);

        return [$issue,$itemId,$medicine->id];
    }
}
