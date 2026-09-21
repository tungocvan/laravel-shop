<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_drug_bid_award_commercial_policies', function (Blueprint $table) {
            $table->id();
            $table->string('result_key')->index('pharma_com_policy_result_idx');
            $table->string('bidding_notice_code')->nullable()->index('pharma_com_policy_tbmt_idx');
            $table->string('name');
            $table->string('commission_type', 32);
            $table->decimal('commission_value', 20, 4);
            $table->string('commission_basis', 40);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('status', 24)->default('draft')->index('pharma_com_policy_status_idx');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pharma_drug_bid_award_commercial_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commercial_policy_id');
            $table->foreign('commercial_policy_id', 'pharma_com_assign_policy_fk')->references('id')->on('pharma_drug_bid_award_commercial_policies')->restrictOnDelete();
            $table->unsignedBigInteger('drug_bid_award_id');
            $table->foreign('drug_bid_award_id', 'pharma_com_assign_award_fk')->references('id')->on('pharma_drug_bid_awards')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id', 'pharma_com_assign_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->string('assignment_role', 50)->nullable();
            $table->decimal('share_percentage', 7, 4)->default(100);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('status', 24)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('ended_by')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('end_reason')->nullable();
            $table->timestamps();
            $table->index(['commercial_policy_id', 'drug_bid_award_id', 'status'], 'pharma_com_assign_policy_award_idx');
            $table->index(['user_id', 'status'], 'pharma_com_assign_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_drug_bid_award_commercial_assignments');
        Schema::dropIfExists('pharma_drug_bid_award_commercial_policies');
    }
};
