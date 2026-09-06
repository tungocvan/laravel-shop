<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_official_source_facilities', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50);
            $table->string('external_id', 191);
            $table->string('facility_name', 500);
            $table->string('normalized_name', 500);
            $table->string('source_province_code', 50);
            $table->string('province_name', 191)->nullable();
            $table->string('source_district_code', 50)->nullable();
            $table->string('district_name', 191)->nullable();
            $table->json('raw_payload')->nullable();
            $table->char('payload_hash', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('last_sync_batch_id')->nullable()->constrained('pharma_official_source_sync_batches')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source', 'external_id'], 'pharma_source_facility_identity_unique');
            $table->index(['source', 'source_province_code', 'is_active'], 'pharma_source_facility_scope_idx');
            $table->index(['source', 'source_district_code'], 'pharma_source_facility_district_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_official_source_facilities');
    }
};
