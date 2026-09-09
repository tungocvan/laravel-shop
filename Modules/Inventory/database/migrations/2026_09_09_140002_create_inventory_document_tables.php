<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 80)->unique();
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
            $table->string('status', 30)->default('DRAFT')->index();
            $table->string('source_type', 40)->default('manual')->index();
            $table->string('source_identity_key', 191)->nullable()->index();
            $table->unsignedBigInteger('partner_id')->nullable()->index();
            $table->string('seller_name_snapshot')->nullable();
            $table->string('seller_tax_code_snapshot', 50)->nullable();
            $table->text('seller_address_snapshot')->nullable();
            $table->dateTime('document_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('reversal_of_type', 50)->nullable();
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_identity_key'], 'inventory_receipt_source_unique');
        });

        Schema::create('inventory_receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('receipt_id')->constrained('inventory_receipts')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->restrictOnDelete();
            $table->string('classification', 30)->default('STOCK');
            $table->decimal('source_quantity', 20, 6);
            $table->string('source_uom', 80)->nullable();
            $table->decimal('conversion_factor', 20, 8)->default(1);
            $table->decimal('base_quantity', 20, 6);
            $table->string('base_uom', 80)->nullable();
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->string('lot_number', 150)->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->restrictOnDelete();
            $table->string('source_line_key', 191)->nullable();
            $table->string('description_snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['receipt_id', 'line_number']);
        });

        Schema::create('inventory_issues', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 80)->unique();
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
            $table->string('status', 30)->default('DRAFT')->index();
            $table->string('reason', 80)->nullable()->index();
            $table->unsignedBigInteger('partner_id')->nullable()->index();
            $table->dateTime('document_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_issue_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('issue_id')->constrained('inventory_issues')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->restrictOnDelete();
            $table->decimal('base_quantity', 20, 6);
            $table->string('base_uom', 80);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['issue_id', 'line_number']);
        });

        Schema::create('inventory_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 80)->unique();
            $table->foreignId('source_warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
            $table->string('status', 30)->default('DRAFT')->index();
            $table->dateTime('document_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_transfer_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transfer_id')->constrained('inventory_transfers')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->restrictOnDelete();
            $table->decimal('base_quantity', 20, 6);
            $table->string('base_uom', 80);
            $table->timestamps();
            $table->unique(['transfer_id', 'line_number']);
        });

        Schema::create('inventory_stocktakes', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 80)->unique();
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
            $table->string('status', 30)->default('DRAFT')->index();
            $table->timestamp('counted_at')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stocktake_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stocktake_id')->constrained('inventory_stocktakes')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->restrictOnDelete();
            $table->decimal('system_quantity_snapshot', 20, 6);
            $table->decimal('counted_quantity', 20, 6);
            $table->decimal('posted_variance', 20, 6)->nullable();
            $table->string('base_uom', 80);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['stocktake_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocktake_lines');
        Schema::dropIfExists('inventory_stocktakes');
        Schema::dropIfExists('inventory_transfer_lines');
        Schema::dropIfExists('inventory_transfers');
        Schema::dropIfExists('inventory_issue_lines');
        Schema::dropIfExists('inventory_issues');
        Schema::dropIfExists('inventory_receipt_lines');
        Schema::dropIfExists('inventory_receipts');
    }
};
