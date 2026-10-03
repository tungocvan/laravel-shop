<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_issue_commissions', function (Blueprint $table) {
            $table->dropUnique('ph_inv_issue_comm_item_type_unique');
            $table->unique('original_commission_id','ph_inv_issue_comm_original_unique');
            $table->index(['issue_item_id','entry_type'],'ph_inv_issue_comm_item_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_issue_commissions', function (Blueprint $table) {
            $table->dropIndex('ph_inv_issue_comm_item_type_idx');
            $table->dropUnique('ph_inv_issue_comm_original_unique');
            $table->unique(['issue_item_id','entry_type'],'ph_inv_issue_comm_item_type_unique');
        });
    }
};
