<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_medicine_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('pharma_medicines')->cascadeOnDelete();
            $table->string('source_system', 64);
            $table->string('source_record_type', 96);
            $table->string('source_record_key', 191);
            $table->string('source_reference', 255)->nullable();
            $table->string('payload_hash', 64)->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('match_method', 64)->nullable();
            $table->unsignedTinyInteger('match_confidence')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_system', 'source_record_type', 'source_record_key'],
                'pharma_medicine_source_identity_unique'
            );
            $table->index(['medicine_id', 'is_active'], 'pharma_medicine_sources_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_medicine_sources');
    }
};
