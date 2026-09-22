<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_drug_bid_award_product_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drug_bid_award_id');
            $table->foreign('drug_bid_award_id', 'pharma_prod_policy_award_fk')->references('id')->on('pharma_drug_bid_awards')->restrictOnDelete();
            $table->decimal('commission_percentage', 7, 4)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique('drug_bid_award_id', 'pharma_prod_policy_award_uq');
        });

        Schema::create('pharma_drug_bid_award_management_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drug_bid_award_id');
            $table->foreign('drug_bid_award_id', 'pharma_mgmt_assign_award_fk')->references('id')->on('pharma_drug_bid_awards')->restrictOnDelete();
            $table->unsignedBigInteger('partner_id');
            $table->foreign('partner_id', 'pharma_mgmt_assign_partner_fk')->references('id')->on('partners')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id', 'pharma_mgmt_assign_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->string('status', 24)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['drug_bid_award_id', 'partner_id'], 'pharma_mgmt_assign_award_partner_uq');
            $table->index(['user_id', 'status'], 'pharma_mgmt_assign_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_drug_bid_award_management_assignments');
        Schema::dropIfExists('pharma_drug_bid_award_product_policies');
    }
};
