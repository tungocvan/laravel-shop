<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_inventory_balances', function (Blueprint $table): void {
            $table->decimal('manual_cost_price', 18, 2)->nullable()->after('quantity_on_hand');
        });

        Schema::create('pharma_inventory_cost_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_balance_id')->constrained('pharma_inventory_balances')->cascadeOnDelete();
            $table->decimal('old_manual_cost_price', 18, 2)->nullable();
            $table->decimal('new_manual_cost_price', 18, 2)->nullable();
            $table->string('reason', 500);
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['inventory_balance_id', 'created_at'], 'inv_cost_adj_balance_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_inventory_cost_adjustments');
        Schema::table('pharma_inventory_balances', function (Blueprint $table): void {
            $table->dropColumn('manual_cost_price');
        });
    }
};
