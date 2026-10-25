<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_debt_allocation_ownerships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obligation_id')->constrained('finance_debt_obligations');
            $table->foreignId('allocation_id')->constrained('finance_allocations');
            $table->foreignId('mapping_id')->unique()->constrained('finance_debt_settlement_mappings');
            $table->unsignedBigInteger('authorized_by');
            $table->string('authorization_reason', 500);
            $table->timestamps();
            $table->unique('allocation_id', 'finance_debt_allocation_single_owner');
            $table->index('obligation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_debt_allocation_ownerships');
    }
};
