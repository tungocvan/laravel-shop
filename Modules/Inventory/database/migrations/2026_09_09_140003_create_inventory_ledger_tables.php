<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->restrictOnDelete();
            $table->string('dimension_key', 64)->unique();
            $table->decimal('quantity_on_hand', 20, 6)->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->index(['warehouse_id', 'inventory_item_id']);
            $table->index(['inventory_item_id', 'lot_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->string('movement_key', 64)->unique();
            $table->string('movement_type', 50)->index();
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->restrictOnDelete();
            $table->string('dimension_key', 64)->index();
            $table->decimal('quantity_delta', 20, 6);
            $table->string('base_uom', 80);
            $table->string('document_type', 50)->index();
            $table->unsignedBigInteger('document_id')->index();
            $table->unsignedBigInteger('document_line_id')->nullable()->index();
            $table->string('movement_role', 50);
            $table->string('source_type', 50)->nullable()->index();
            $table->string('source_identity_key', 191)->nullable()->index();
            $table->timestamp('occurred_at');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversal_of_movement_id')->nullable()->constrained('inventory_movements')->restrictOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['warehouse_id', 'inventory_item_id', 'lot_id'], 'inventory_movement_stock_dimension');
            $table->index(['document_type', 'document_id'], 'inventory_movement_document');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_balances');
    }
};
