<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_debt_settlement_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obligation_id')->constrained('finance_debt_obligations');
            $table->foreignId('voucher_id')->constrained('finance_vouchers');
            $table->string('operation', 20);
            $table->decimal('amount', 20, 2);
            $table->foreignId('reverses_mapping_id')->nullable()
                ->constrained('finance_debt_settlement_mappings');
            $table->timestamps();
            $table->unique(['obligation_id', 'voucher_id', 'operation'], 'finance_debt_mapping_once_unique');
            $table->unique('reverses_mapping_id', 'finance_debt_reversal_once_unique');
            $table->index(['obligation_id', 'operation'], 'finance_debt_mapping_obligation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_debt_settlement_mappings');
    }
};
