<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_issues', function (Blueprint $table) {
            $table->string('issue_source',24)->default('normal')->after('recipient_name');
            $table->unsignedBigInteger('bid_partner_id')->nullable()->after('issue_source');
            $table->string('bid_investor_code',100)->nullable()->after('bid_partner_id');
            $table->string('bid_investor_name')->nullable()->after('bid_investor_code');
            $table->index(['issue_source','bid_partner_id'],'ph_inv_issue_source_partner_idx');
        });
        Schema::table('pharma_inventory_issue_items', function (Blueprint $table) {
            $table->unsignedBigInteger('drug_bid_award_id')->nullable()->after('medicine_id');
            $table->unsignedBigInteger('drug_bid_award_allocation_id')->nullable()->after('drug_bid_award_id');
            $table->index('drug_bid_award_allocation_id','ph_inv_issue_item_bid_alloc_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_issue_items', function (Blueprint $table) {
            $table->dropIndex('ph_inv_issue_item_bid_alloc_idx');
            $table->dropColumn(['drug_bid_award_id','drug_bid_award_allocation_id']);
        });
        Schema::table('pharma_inventory_issues', function (Blueprint $table) {
            $table->dropIndex('ph_inv_issue_source_partner_idx');
            $table->dropColumn(['issue_source','bid_partner_id','bid_investor_code','bid_investor_name']);
        });
    }
};
