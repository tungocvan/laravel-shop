<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_inventory_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('source', 32)->default('gdt_detail');
            $table->char('payload_hash', 64)->nullable();
            $table->string('status', 32)->default('PENDING');
            $table->json('raw_payload')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('normalized_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['invoice_id', 'source']);
            $table->index(['status', 'fetched_at']);
        });

        Schema::create('invoice_inventory_staging_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('invoice_inventory_snapshots')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('source_line_key', 64);
            $table->text('raw_description');
            $table->string('source_product_code')->nullable();
            $table->decimal('source_quantity', 18, 4)->nullable();
            $table->string('source_uom', 64)->nullable();
            $table->decimal('unit_price', 18, 4)->nullable();
            $table->decimal('line_amount', 18, 4)->nullable();
            $table->string('normalized_name')->nullable();
            $table->string('strength')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('package_spec')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('normalized_uom', 64)->nullable();
            $table->string('lot_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('normalization_status', 32)->default('RAW');
            $table->json('raw_payload');
            $table->json('normalization_meta')->nullable();
            $table->timestamps();
            $table->unique(['snapshot_id', 'source_line_key'], 'invoice_inventory_staging_line_unique');
            $table->index(['normalization_status', 'normalized_name'], 'invoice_inventory_staging_normalized_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_inventory_staging_lines');
        Schema::dropIfExists('invoice_inventory_snapshots');
    }
};
