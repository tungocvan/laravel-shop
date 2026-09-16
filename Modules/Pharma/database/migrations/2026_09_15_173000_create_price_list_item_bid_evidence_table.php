<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_price_list_item_bid_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_list_item_id')->unique()->constrained('pharma_price_list_items')->cascadeOnDelete();
            $table->foreignId('drug_bid_award_id')->nullable()->constrained('pharma_drug_bid_awards')->nullOnDelete();
            $table->string('source_system', 80)->nullable();
            $table->string('source_record_key', 191)->nullable();
            $table->decimal('bid_price', 18, 4)->nullable();
            $table->decimal('quantity', 18, 4)->nullable();
            $table->string('unit', 80)->nullable();
            $table->string('investor_code', 120)->nullable();
            $table->string('investor_name')->nullable();
            $table->string('contractor_code', 120)->nullable();
            $table->string('contractor_name')->nullable();
            $table->string('decision_number', 191)->nullable();
            $table->date('award_date')->nullable();
            $table->foreignId('medicine_id')->nullable()->constrained('pharma_medicines')->nullOnDelete();
            $table->foreignId('medicine_variant_id')->nullable()->constrained('pharma_medicine_variants')->nullOnDelete();
            $table->foreignId('medicine_package_id')->nullable()->constrained('pharma_medicine_packages')->nullOnDelete();
            $table->timestamp('captured_at');
            $table->foreignId('captured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['medicine_variant_id', 'medicine_package_id'], 'pharma_pl_bid_evidence_identity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_price_list_item_bid_evidence');
    }
};
