<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('kind', 20);
            $table->char('currency', 3)->default('VND');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('finance_source_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('source_module', 64);
            $table->string('source_type', 80);
            $table->string('source_document_id', 100);
            $table->string('source_document_code', 100);
            $table->string('direction', 20);
            $table->char('currency', 3);
            $table->decimal('original_amount', 20, 2);
            $table->string('counterparty')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->unique(['source_module', 'source_type', 'source_document_id'], 'finance_source_identity_unique');
        });

        Schema::create('finance_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 50)->unique();
            $table->string('direction', 10);
            $table->string('status', 20)->default('draft');
            $table->foreignId('account_id')->constrained('finance_accounts');
            $table->char('currency', 3);
            $table->decimal('amount', 20, 2);
            $table->date('voucher_date');
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'voucher_date']);
        });

        Schema::create('finance_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('voucher_id')->constrained('finance_vouchers');
            $table->foreignId('source_document_id')->constrained('finance_source_documents');
            $table->decimal('amount', 20, 2);
            $table->timestamps();
            $table->unique(['voucher_id', 'source_document_id'], 'finance_allocation_unique');
        });

        Schema::create('finance_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('voucher_id')->constrained('finance_vouchers');
            $table->foreignId('account_id')->constrained('finance_accounts');
            $table->string('entry_type', 20);
            $table->decimal('signed_amount', 20, 2);
            $table->char('currency', 3);
            $table->timestamp('effective_at');
            $table->timestamps();
            $table->index(['account_id', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_ledger_entries');
        Schema::dropIfExists('finance_allocations');
        Schema::dropIfExists('finance_vouchers');
        Schema::dropIfExists('finance_source_documents');
        Schema::dropIfExists('finance_accounts');
    }
};
