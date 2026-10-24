<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_debt_obligations', function (Blueprint $table): void {
            $table->id();
            $table->string('source_module', 64)->nullable();
            $table->string('source_type', 80)->nullable();
            $table->string('source_document_id', 100)->nullable();
            $table->string('reference_number', 100);
            $table->string('direction', 20);
            $table->string('status', 20)->default('draft');
            $table->string('counterparty_name');
            $table->string('counterparty_tax_code', 40)->nullable();
            $table->date('recognized_on')->nullable();
            $table->date('due_on')->nullable();
            $table->char('currency', 3)->default('VND');
            $table->decimal('original_amount', 20, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['source_module', 'source_type', 'source_document_id'],
                'finance_debt_source_identity_unique'
            );
            $table->index(['direction', 'status', 'due_on'], 'finance_debt_due_index');
            $table->index(['counterparty_tax_code', 'direction'], 'finance_debt_counterparty_index');
        });

        Schema::create('finance_debt_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obligation_id')->constrained('finance_debt_obligations');
            $table->string('event_type', 30);
            $table->decimal('signed_amount', 20, 2);
            $table->date('effective_on');
            $table->string('reason', 500);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['obligation_id', 'effective_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_debt_events');
        Schema::dropIfExists('finance_debt_obligations');
    }
};
