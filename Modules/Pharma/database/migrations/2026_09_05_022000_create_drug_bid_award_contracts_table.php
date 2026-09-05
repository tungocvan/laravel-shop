<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_drug_bid_award_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_bid_award_allocation_id')->constrained('pharma_drug_bid_award_allocations')->restrictOnDelete();
            $table->string('contract_number');
            $table->date('contract_date')->nullable();
            $table->decimal('contract_quantity', 20, 4);
            $table->decimal('contract_value', 20, 4)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 24)->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['drug_bid_award_allocation_id', 'contract_number'], 'pharma_award_contract_number_unique');
            $table->index(['drug_bid_award_allocation_id', 'status'], 'pharma_award_contract_status_index');
            $table->index('contract_date', 'pharma_award_contract_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_drug_bid_award_contracts');
    }
};
