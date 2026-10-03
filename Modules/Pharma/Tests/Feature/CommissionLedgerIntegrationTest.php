<?php

namespace Modules\Pharma\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryIssueCommission;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\CommissionQueryService;
use Modules\Pharma\Services\DrugBidCommissionService;
use Tests\TestCase;

final class CommissionLedgerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createCommissionSchema();
    }

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

    private function createCommissionSchema(): void
    {
        Schema::create('pharma_medicines',function(Blueprint $table): void {
            $table->id(); $table->string('active_ingredients'); $table->string('concentration'); $table->string('name');
            $table->string('dosage_form'); $table->string('route_of_administration'); $table->string('unit');
            $table->string('packaging_specification'); $table->string('registration_number'); $table->string('shelf_life');
            $table->string('registered_company'); $table->string('manufacturing_company'); $table->string('manufacturing_country');
            $table->string('medicine_code')->nullable(); $table->timestamps();
        });
        Schema::create('pharma_inventory_warehouses',function(Blueprint $table): void {
            $table->id(); $table->string('code')->unique(); $table->string('name'); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('pharma_inventory_issues',function(Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('warehouse_id'); $table->string('number')->unique(); $table->date('issue_date');
            $table->string('status'); $table->timestamp('posted_at')->nullable(); $table->timestamps();
        });
        Schema::create('pharma_inventory_issue_items',function(Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('issue_id'); $table->unsignedBigInteger('medicine_id');
            $table->string('batch_number'); $table->date('expiry_date'); $table->decimal('quantity',15,3);
            $table->decimal('unit_price',18,4); $table->timestamps();
        });
        Schema::create('pharma_inventory_issue_commissions',function(Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('issue_id'); $table->unsignedBigInteger('issue_item_id');
            $table->unsignedBigInteger('original_commission_id')->nullable()->unique(); $table->unsignedBigInteger('drug_bid_award_id')->nullable();
            $table->unsignedBigInteger('drug_bid_award_allocation_id')->nullable(); $table->unsignedBigInteger('price_list_id')->nullable();
            $table->unsignedBigInteger('price_list_item_id')->nullable(); $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedBigInteger('medicine_id'); $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('quantity',15,3); $table->decimal('unit_price',15,4); $table->decimal('sale_price_snapshot',15,4)->nullable();
            $table->decimal('receivable_price_snapshot',15,4)->nullable(); $table->decimal('revenue_amount',18,2);
            $table->decimal('commission_percentage',8,4)->nullable(); $table->decimal('commission_amount',18,2)->default(0);
            $table->string('entry_type',20); $table->string('source_type',20); $table->string('status',20);
            $table->string('resolution_note',500)->nullable(); $table->timestamp('calculated_at'); $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
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
