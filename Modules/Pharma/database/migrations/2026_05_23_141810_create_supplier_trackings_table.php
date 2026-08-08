<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_supplier_trackings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medicine_id')
                ->constrained('pharma_medicines')
                ->cascadeOnDelete();

            $table->date('working_date')->nullable();

            $table->string('supplier_name');
            $table->string('supplier_representative')->nullable();
            $table->string('area')->nullable();

            $table->decimal('import_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('invoice_price', 15, 2)->default(0);

            $table->decimal('invoice_difference_amount', 15, 2)->default(0);
            $table->decimal('invoice_difference_percent', 8, 2)->default(0);
            $table->decimal('invoice_difference_fee', 15, 2)->default(0);

            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('gross_profit_percent', 8, 2)->default(0);

            $table->decimal('committed_quantity', 15, 2)->nullable();
            $table->string('unit')->nullable();

            $table->decimal('deposit_amount', 15, 2)->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->text('contract_url')->nullable();
            $table->string('status')->default('active');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['medicine_id', 'supplier_name']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_supplier_trackings');
    }
};
