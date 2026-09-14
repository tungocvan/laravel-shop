<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_price_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('type', 20);
            $table->unsignedBigInteger('partner_id')->nullable()->index();
            $table->string('status', 20)->default('draft')->index();
            $table->date('effective_from')->nullable()->index();
            $table->date('effective_to')->nullable()->index();
            $table->string('currency', 3)->default('VND');
            $table->integer('priority')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status', 'effective_from', 'effective_to'], 'pharma_price_lists_resolution_idx');
            $table->index(['partner_id', 'status', 'effective_from', 'effective_to'], 'pharma_price_lists_partner_resolution_idx');
        });

        Schema::create('pharma_price_list_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_list_id')->constrained('pharma_price_lists')->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained('pharma_medicines')->restrictOnDelete();
            $table->foreignId('medicine_variant_id')->constrained('pharma_medicine_variants')->restrictOnDelete();
            $table->foreignId('medicine_package_id')->nullable()->constrained('pharma_medicine_packages')->restrictOnDelete();
            $table->string('identity_key', 120);
            $table->decimal('declared_price_snapshot', 18, 2)->nullable();
            $table->decimal('company_sale_price', 18, 2)->nullable();
            $table->decimal('actual_receivable_price', 18, 2)->nullable();
            $table->decimal('invoice_price', 18, 2)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['price_list_id', 'identity_key'], 'pharma_price_list_items_identity_unique');
            $table->index(['medicine_variant_id', 'medicine_package_id', 'status'], 'pharma_price_list_items_resolution_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_price_list_items');
        Schema::dropIfExists('pharma_price_lists');
    }
};
