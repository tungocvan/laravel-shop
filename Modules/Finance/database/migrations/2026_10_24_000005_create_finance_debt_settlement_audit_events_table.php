<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_debt_settlement_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obligation_id')->constrained('finance_debt_obligations');
            $table->foreignId('mapping_id')->unique()->constrained('finance_debt_settlement_mappings');
            $table->foreignId('voucher_id')->constrained('finance_vouchers');
            $table->string('operation', 20);
            $table->foreignId('origin_mapping_id')->nullable()->constrained('finance_debt_settlement_mappings');
            $table->unsignedBigInteger('actor_id');
            $table->string('reason', 500);
            $table->string('idempotency_key', 128);
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['obligation_id', 'operation'], 'finance_debt_audit_obligation_operation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_debt_settlement_audit_events');
    }
};
