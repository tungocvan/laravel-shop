<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_reminder_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_contract_id')->unique()->constrained('loan_contracts')->restrictOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('recipient_email');
            $table->json('days_before');
            $table->timestamps();
        });
        Schema::create('loan_reminder_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_repayment_schedule_id')->constrained('loan_repayment_schedules')->restrictOnDelete();
            $table->unsignedTinyInteger('days_before');
            $table->date('due_at_snapshot');
            $table->string('recipient_email');
            $table->string('status')->default('reserved');
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unique(['loan_repayment_schedule_id', 'days_before', 'due_at_snapshot'], 'loan_reminder_delivery_once_uq');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('loan_reminder_deliveries');
        Schema::dropIfExists('loan_reminder_settings');
    }
};
