<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_medicine_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source', 64)->default('owner_master_excel');
            $table->string('source_file')->nullable();
            $table->string('status', 32)->default('staged');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('new_rows')->default(0);
            $table->unsignedInteger('update_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('conflict_rows')->default(0);
            $table->unsignedInteger('review_rows')->default(0);
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'pharma_medicine_import_batch_status_index');
        });

        Schema::create('pharma_medicine_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('pharma_medicine_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('source_row');
            $table->string('classification', 32)->default('needs_review');
            $table->string('resolution_reason', 128)->nullable();
            $table->foreignId('matched_medicine_id')->nullable()->constrained('pharma_medicines')->nullOnDelete();
            $table->foreignId('matched_variant_id')->nullable()->constrained('pharma_medicine_variants')->nullOnDelete();
            $table->json('raw_payload');
            $table->json('normalized_payload')->nullable();
            $table->string('medicine_identity_key', 64)->nullable();
            $table->string('variant_identity_key', 64)->nullable();
            $table->string('payload_hash', 64);
            $table->boolean('selected')->default(false);
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'source_row'], 'pharma_medicine_import_row_unique');
            $table->index(['batch_id', 'classification'], 'pharma_medicine_import_classification_index');
            $table->index('payload_hash', 'pharma_medicine_import_payload_hash_index');
            $table->index('variant_identity_key', 'pharma_medicine_import_variant_identity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_medicine_import_rows');
        Schema::dropIfExists('pharma_medicine_import_batches');
    }
};
