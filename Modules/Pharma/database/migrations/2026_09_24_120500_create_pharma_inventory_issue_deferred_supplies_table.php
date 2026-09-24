<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pharma_inventory_issue_deferred_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('pharma_inventory_issues')->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained('pharma_medicines');
            $table->unsignedBigInteger('drug_bid_award_id')->nullable();
            $table->unsignedBigInteger('drug_bid_award_allocation_id')->nullable();
            $table->decimal('quantity',15,3);
            $table->date('expected_supply_date')->nullable();
            $table->text('note');
            $table->string('status',20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['issue_id','status'],'ph_inv_issue_deferred_status_idx');
            $table->index('drug_bid_award_allocation_id','ph_inv_issue_deferred_alloc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_inventory_issue_deferred_supplies');
    }
};
