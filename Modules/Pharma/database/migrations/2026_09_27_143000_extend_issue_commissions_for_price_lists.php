<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_issue_commissions', function (Blueprint $table) {
            $table->string('source_type',20)->default('bid')->after('entry_type')->index();
            $table->unsignedBigInteger('price_list_id')->nullable()->after('drug_bid_award_allocation_id');
            $table->unsignedBigInteger('price_list_item_id')->nullable()->after('price_list_id');
            $table->decimal('sale_price_snapshot',15,4)->nullable()->after('unit_price');
            $table->decimal('receivable_price_snapshot',15,4)->nullable()->after('sale_price_snapshot');
            $table->index(['source_type','calculated_at'],'ph_inv_issue_comm_source_date_idx');
        });
    }
    public function down(): void
    {
        Schema::table('pharma_inventory_issue_commissions', function (Blueprint $table) {
            $table->dropIndex('ph_inv_issue_comm_source_date_idx');
            $table->dropIndex(['source_type']);
            $table->dropColumn(['source_type','price_list_id','price_list_item_id','sale_price_snapshot','receivable_price_snapshot']);
        });
    }
};
