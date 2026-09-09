<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->unsignedBigInteger('partner_id')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('province_code', 30)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 120)->unique();
            $table->string('display_name');
            $table->string('base_uom', 80);
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('pharma_medicine_id')->nullable()->index();
            $table->boolean('lot_tracking')->default(false);
            $table->boolean('expiry_tracking')->default(false);
            $table->boolean('allow_fractional_quantity')->default(true);
            $table->decimal('reorder_level', 20, 6)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_item_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->unsignedBigInteger('supplier_partner_id')->nullable()->index();
            $table->string('supplier_tax_code', 50)->nullable()->index();
            $table->string('source_product_code', 150)->nullable();
            $table->string('normalized_description_key', 191);
            $table->string('uom_key', 100)->default('');
            $table->string('package_key', 150)->default('');
            $table->string('source', 50)->default('invoices');
            $table->string('alias_key', 64)->unique();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['source', 'supplier_tax_code']);
        });

        Schema::create('inventory_lots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->string('lot_number', 150);
            $table->date('expiry_date')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->unsignedBigInteger('supplier_partner_id')->nullable()->index();
            $table->unsignedBigInteger('source_receipt_line_id')->nullable()->index();
            $table->string('identity_key', 64)->unique();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['inventory_item_id', 'lot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lots');
        Schema::dropIfExists('inventory_item_aliases');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_warehouses');
    }
};
