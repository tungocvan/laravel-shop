<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_drug_bid_award_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('drug_bid_award_id')->constrained('pharma_drug_bid_awards')->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained('pharma_medicines')->nullOnDelete();
            $table->foreignId('medicine_variant_id')->nullable()->constrained('pharma_medicine_variants')->nullOnDelete();
            $table->foreignId('medicine_package_id')->nullable()->constrained('pharma_medicine_packages')->nullOnDelete();
            $table->string('match_status', 32)->default('unmatched');
            $table->string('match_method', 96)->nullable();
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->string('resolution_level', 32)->nullable();
            $table->string('review_status', 32)->default('pending');
            $table->boolean('is_manual')->default(false);
            $table->unsignedBigInteger('matched_by')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->string('source_identity_hash', 64)->nullable();
            $table->string('matched_identity_hash', 64)->nullable();
            $table->string('review_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('drug_bid_award_id', 'pharma_drug_bid_award_matches_award_unique');
            $table->index(['medicine_id', 'match_status'], 'pharma_bid_match_medicine_status_index');
            $table->index(['medicine_variant_id', 'match_status'], 'pharma_bid_match_variant_status_index');
            $table->index(['medicine_package_id', 'match_status'], 'pharma_bid_match_package_status_index');
            $table->index(['review_status', 'updated_at'], 'pharma_bid_match_review_updated_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_drug_bid_award_matches');
    }
};
