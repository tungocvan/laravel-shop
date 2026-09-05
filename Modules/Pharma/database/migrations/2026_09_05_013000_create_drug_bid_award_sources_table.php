<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_drug_bid_award_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_bid_award_id')->constrained('pharma_drug_bid_awards')->cascadeOnDelete();
            $table->string('source_system', 64);
            $table->string('source_record_type', 96);
            $table->string('source_record_key', 191);
            $table->string('source_reference', 255)->nullable();
            $table->string('source_channel', 64)->nullable();
            $table->string('sync_source', 255)->nullable();
            $table->string('source_payload_hash', 64)->nullable();
            $table->timestamp('source_observed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_system', 'source_record_type', 'source_record_key'],
                'pharma_drug_award_source_identity_unique'
            );
            $table->index(['drug_bid_award_id', 'is_active'], 'pharma_drug_award_sources_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_drug_bid_award_sources');
    }
};
