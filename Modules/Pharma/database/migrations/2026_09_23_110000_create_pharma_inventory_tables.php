<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pharma_inventory_warehouses', function (Blueprint $table) {
            $table->id(); $table->string('code', 32)->unique(); $table->string('name'); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('pharma_inventory_receipts', function (Blueprint $table) {
            $table->id(); $table->foreignId('warehouse_id')->constrained('pharma_inventory_warehouses');
            $table->string('number', 40)->unique(); $table->date('receipt_date'); $table->string('supplier_name')->nullable();
            $table->string('invoice_number')->nullable(); $table->date('invoice_date')->nullable(); $table->string('status', 16)->default('draft');
            $table->text('notes')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('posted_by')->nullable(); $table->timestamp('posted_at')->nullable(); $table->timestamps();
        });
        Schema::create('pharma_inventory_receipt_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('receipt_id')->constrained('pharma_inventory_receipts')->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained('pharma_medicines'); $table->string('batch_number', 100); $table->date('expiry_date');
            $table->decimal('quantity', 15, 3); $table->decimal('unit_price_ex_vat', 18, 4)->default(0); $table->decimal('vat_rate', 5, 2)->default(0); $table->timestamps();
            $table->index(['medicine_id','batch_number','expiry_date'], 'ph_inv_receipt_med_batch_idx');
        });
        Schema::create('pharma_inventory_issues', function (Blueprint $table) {
            $table->id(); $table->foreignId('warehouse_id')->constrained('pharma_inventory_warehouses');
            $table->string('number', 40)->unique(); $table->date('issue_date'); $table->string('recipient_name')->nullable();
            $table->string('status', 16)->default('draft'); $table->text('notes')->nullable(); $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable(); $table->timestamp('posted_at')->nullable(); $table->timestamps();
        });
        Schema::create('pharma_inventory_issue_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('issue_id')->constrained('pharma_inventory_issues')->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained('pharma_medicines'); $table->string('batch_number', 100); $table->date('expiry_date');
            $table->decimal('quantity', 15, 3); $table->decimal('unit_price', 18, 4)->default(0); $table->timestamps();
            $table->index(['medicine_id','batch_number','expiry_date'], 'ph_inv_issue_med_batch_idx');
        });
        Schema::create('pharma_inventory_balances', function (Blueprint $table) {
            $table->id(); $table->foreignId('warehouse_id')->constrained('pharma_inventory_warehouses');
            $table->foreignId('medicine_id')->constrained('pharma_medicines'); $table->string('batch_number', 100); $table->date('expiry_date');
            $table->decimal('opening_quantity', 15, 3)->default(0); $table->decimal('quantity_on_hand', 15, 3)->default(0); $table->timestamps();
            $table->unique(['warehouse_id','medicine_id','batch_number','expiry_date'], 'ph_inv_balance_identity_uq');
        });
        Schema::create('pharma_inventory_transactions', function (Blueprint $table) {
            $table->id(); $table->foreignId('warehouse_id')->constrained('pharma_inventory_warehouses'); $table->foreignId('medicine_id')->constrained('pharma_medicines');
            $table->string('batch_number', 100); $table->date('expiry_date'); $table->string('type', 20); $table->decimal('quantity_delta', 15, 3);
            $table->decimal('balance_after', 15, 3); $table->nullableMorphs('source'); $table->unsignedBigInteger('created_by')->nullable(); $table->text('notes')->nullable(); $table->timestamps();
            $table->index(['warehouse_id','medicine_id','batch_number','expiry_date'], 'ph_inv_tx_lookup_idx');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pharma_inventory_transactions'); Schema::dropIfExists('pharma_inventory_balances');
        Schema::dropIfExists('pharma_inventory_issue_items'); Schema::dropIfExists('pharma_inventory_issues');
        Schema::dropIfExists('pharma_inventory_receipt_items'); Schema::dropIfExists('pharma_inventory_receipts'); Schema::dropIfExists('pharma_inventory_warehouses');
    }
};