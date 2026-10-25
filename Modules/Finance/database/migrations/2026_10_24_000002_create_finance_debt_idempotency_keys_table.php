<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_debt_idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obligation_id')->constrained('finance_debt_obligations');
            $table->string('operation', 20);
            $table->string('idempotency_key', 128);
            $table->timestamps();
            $table->unique(
                ['obligation_id', 'operation', 'idempotency_key'],
                'finance_debt_idempotency_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_debt_idempotency_keys');
    }
};
