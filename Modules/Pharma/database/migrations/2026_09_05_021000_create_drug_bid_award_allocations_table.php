<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_drug_bid_award_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_bid_award_id')->constrained('pharma_drug_bid_awards')->restrictOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->decimal('allocated_quantity', 20, 4);
            $table->string('status', 24)->default('active');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['drug_bid_award_id', 'partner_id'], 'pharma_award_allocation_partner_unique');
            $table->index(['drug_bid_award_id', 'status'], 'pharma_award_allocation_status_index');
            $table->index(['partner_id', 'status'], 'pharma_award_allocation_partner_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_drug_bid_award_allocations');
    }
};
