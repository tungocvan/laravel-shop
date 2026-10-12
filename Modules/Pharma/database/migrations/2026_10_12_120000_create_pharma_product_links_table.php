<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_product_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('wp_products')->restrictOnDelete();
            $table->foreignId('medicine_id')->constrained('pharma_medicines')->restrictOnDelete();
            $table->foreignId('medicine_variant_id')->constrained('pharma_medicine_variants')->restrictOnDelete();
            $table->foreignId('medicine_package_id')->nullable()->constrained('pharma_medicine_packages')->restrictOnDelete();
            $table->string('source_key', 128)->unique();
            $table->string('sync_status', 32)->default('pending');
            $table->char('source_fingerprint', 64)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['medicine_id', 'sync_status'], 'pharma_product_links_medicine_status_idx');
            $table->index('medicine_variant_id', 'pharma_product_links_variant_idx');
            $table->index('medicine_package_id', 'pharma_product_links_package_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_product_links');
    }
};
