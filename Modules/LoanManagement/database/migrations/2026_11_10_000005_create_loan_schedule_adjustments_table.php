<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_schedule_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_repayment_schedule_id')->constrained('loan_repayment_schedules')->restrictOnDelete();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('before_json');
            $table->json('after_json');
            $table->timestamp('created_at');
            $table->index(['loan_repayment_schedule_id', 'created_at'], 'loan_schedule_adjustments_history_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedule_adjustments');
    }
};
