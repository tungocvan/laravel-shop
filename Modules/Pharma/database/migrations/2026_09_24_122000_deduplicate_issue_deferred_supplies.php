<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $duplicates=DB::table('pharma_inventory_issue_deferred_supplies')
            ->whereNotNull('drug_bid_award_allocation_id')
            ->select('issue_id','drug_bid_award_allocation_id','status',DB::raw('MAX(id) as keep_id'),DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('issue_id','drug_bid_award_allocation_id','status')
            ->havingRaw('COUNT(*) > 1')->get();

        foreach($duplicates as $duplicate){
            DB::table('pharma_inventory_issue_deferred_supplies')
                ->where('issue_id',$duplicate->issue_id)
                ->where('drug_bid_award_allocation_id',$duplicate->drug_bid_award_allocation_id)
                ->where('status',$duplicate->status)
                ->where('id','<>',$duplicate->keep_id)
                ->delete();
        }

        Schema::table('pharma_inventory_issue_deferred_supplies', function(Blueprint $table){
            $table->unique(
                ['issue_id','drug_bid_award_allocation_id','status'],
                'ph_inv_issue_deferred_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_issue_deferred_supplies', function(Blueprint $table){
            $table->dropUnique('ph_inv_issue_deferred_unique');
        });
    }
};
