<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_payment_id')->constrained('loan_payments')->restrictOnDelete();
            $table->foreignId('loan_repayment_schedule_id')->constrained('loan_repayment_schedules')->restrictOnDelete();
            $table->decimal('principal_allocated', 20, 2)->default(0);
            $table->decimal('interest_allocated', 20, 2)->default(0);
            $table->decimal('fees_allocated', 20, 2)->default(0);
            $table->unique(['loan_payment_id', 'loan_repayment_schedule_id'], 'loan_alloc_payment_schedule_uq');
            $table->index('loan_repayment_schedule_id', 'loan_alloc_schedule_idx');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payment_allocations');
    }
};
