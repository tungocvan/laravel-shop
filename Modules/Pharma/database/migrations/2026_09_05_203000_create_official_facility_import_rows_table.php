<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_official_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('pharma_official_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('external_id', 191)->nullable()->index();
            $table->string('facility_name')->nullable();
            $table->string('normalized_name')->nullable()->index();
            $table->string('tax_code', 50)->nullable()->index();
            $table->text('address')->nullable();
            $table->text('normalized_address')->nullable();
            $table->string('province_code', 20)->nullable()->index();
            $table->string('source_province_code', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('classification', 30)->index();
            $table->string('match_method', 50)->nullable();
            $table->foreignId('matched_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->json('validation_errors')->nullable();
            $table->json('match_context')->nullable();
            $table->boolean('is_selected')->default(false)->index();
            $table->string('resolution_status', 30)->nullable();
            $table->foreignId('resolved_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->string('import_status', 30)->nullable()->index();
            $table->foreignId('imported_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_official_import_rows');
    }
};
