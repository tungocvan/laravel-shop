<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_lenders', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->enum('type', ['individual', 'company', 'credit_institution']);
            $table->string('tax_identifier')->nullable();
            $table->string('contact')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lender_id')->constrained('loan_lenders')->restrictOnDelete();
            $table->string('contract_number')->unique();
            $table->decimal('committed_amount', 20, 2);
            $table->decimal('annual_rate_percent', 9, 5);
            $table->unsignedSmallInteger('day_count_basis')->default(365);
            $table->date('signed_at');
            $table->date('matures_at');
            $table->enum('status', ['draft', 'active', 'closed', 'cancelled'])->default('draft');
            $table->timestamps();
        });

        Schema::create('loan_disbursements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_contract_id')->constrained('loan_contracts')->restrictOnDelete();
            $table->date('disbursed_at');
            $table->decimal('amount', 20, 2);
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_repayment_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_contract_id')->constrained('loan_contracts')->restrictOnDelete();
            $table->unsignedInteger('installment');
            $table->date('due_at');
            $table->decimal('principal_due', 20, 2)->default(0);
            $table->decimal('interest_due', 20, 2)->default(0);
            $table->decimal('fees_due', 20, 2)->default(0);
            $table->unique(['loan_contract_id', 'installment']);
            $table->timestamps();
        });

        Schema::create('loan_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_contract_id')->constrained('loan_contracts')->restrictOnDelete();
            $table->date('paid_at');
            $table->decimal('principal_paid', 20, 2)->default(0);
            $table->decimal('interest_paid', 20, 2)->default(0);
            $table->decimal('fees_paid', 20, 2)->default(0);
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_insurance_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_contract_id')->constrained('loan_contracts')->restrictOnDelete();
            $table->enum('type', ['loan', 'life', 'collateral', 'other']);
            $table->string('provider');
            $table->string('policy_number');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->enum('requirement', ['contractual', 'voluntary', 'unverified'])->default('unverified');
            $table->decimal('insured_amount', 20, 2)->nullable();
            $table->unique(['provider', 'policy_number']);
            $table->timestamps();
        });

        Schema::create('loan_insurance_premium_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_insurance_policy_id');
            $table->foreign('loan_insurance_policy_id', 'loan_ins_premium_policy_fk')
                ->references('id')->on('loan_insurance_policies')->restrictOnDelete();
            $table->date('due_at');
            $table->decimal('amount_due', 20, 2);
            $table->decimal('amount_paid', 20, 2)->default(0);
            $table->date('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['loan_insurance_premium_schedules', 'loan_insurance_policies', 'loan_payments',
            'loan_repayment_schedules', 'loan_disbursements', 'loan_contracts', 'loan_lenders'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
