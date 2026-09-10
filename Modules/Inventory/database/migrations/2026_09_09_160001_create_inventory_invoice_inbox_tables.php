<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_invoice_inbox', function (Blueprint $table): void {
            $table->id();
            $table->string('source_module', 50)->default('Invoices');
            $table->string('source_invoice_identity', 191);
            $table->string('integration_purpose', 50)->default('purchase_receipt');
            $table->string('contract_version', 20)->default('1.0');
            $table->unsignedBigInteger('source_invoice_id')->nullable()->index();
            $table->string('normalized_payload_hash', 64);
            $table->string('processing_status', 40)->default('RECEIVED')->index();
            $table->foreignId('receipt_id')->nullable()->constrained('inventory_receipts')->nullOnDelete();
            $table->unsignedBigInteger('partner_id')->nullable()->index();
            $table->string('seller_name_snapshot')->nullable();
            $table->string('seller_tax_code_snapshot', 50)->nullable()->index();
            $table->text('seller_address_snapshot')->nullable();
            $table->string('invoice_number_snapshot', 120)->nullable();
            $table->string('invoice_symbol_snapshot', 120)->nullable();
            $table->dateTime('issued_at_snapshot')->nullable();
            $table->decimal('amount_before_vat_snapshot', 20, 6)->nullable();
            $table->decimal('vat_amount_snapshot', 20, 6)->nullable();
            $table->decimal('total_amount_snapshot', 20, 6)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['source_module', 'source_invoice_identity', 'integration_purpose'], 'inventory_invoice_inbox_source_unique');
        });

        Schema::create('inventory_invoice_inbox_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inbox_id')->constrained('inventory_invoice_inbox')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('source_line_key', 191);
            $table->string('source_product_code', 150)->nullable();
            $table->string('description_snapshot');
            $table->string('normalized_description_key', 191);
            $table->string('source_uom', 80)->nullable();
            $table->decimal('source_quantity', 20, 6);
            $table->decimal('unit_price', 20, 6)->nullable();
            $table->decimal('line_amount', 20, 6)->nullable();
            $table->string('lot_number', 150)->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->string('classification', 30)->default('UNRESOLVED')->index();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->restrictOnDelete();
            $table->string('match_reason', 120)->nullable();
            $table->decimal('conversion_factor', 20, 8)->default(1);
            $table->decimal('base_quantity', 20, 6)->nullable();
            $table->string('base_uom', 80)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['inbox_id', 'line_number']);
            $table->unique(['inbox_id', 'source_line_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_invoice_inbox_lines');
        Schema::dropIfExists('inventory_invoice_inbox');
    }
};
