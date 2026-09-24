<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pharma_inventory_issue_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('pharma_inventory_issues')->cascadeOnDelete();
            $table->unsignedBigInteger('issue_item_id');
            $table->unsignedBigInteger('original_commission_id')->nullable();
            $table->unsignedBigInteger('drug_bid_award_id')->nullable();
            $table->unsignedBigInteger('drug_bid_award_allocation_id')->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->foreignId('medicine_id')->constrained('pharma_medicines');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('quantity',15,3);
            $table->decimal('unit_price',15,4);
            $table->decimal('revenue_amount',18,2);
            $table->decimal('commission_percentage',8,4)->nullable();
            $table->decimal('commission_amount',18,2)->default(0);
            $table->string('entry_type',20)->default('earned');
            $table->string('status',20)->default('earned');
            $table->string('resolution_note',500)->nullable();
            $table->timestamp('calculated_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['issue_item_id','entry_type'],'ph_inv_issue_comm_item_type_unique');
            $table->index(['user_id','calculated_at'],'ph_inv_issue_comm_user_date_idx');
            $table->index(['issue_id','status'],'ph_inv_issue_comm_issue_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_inventory_issue_commissions');
    }
};
