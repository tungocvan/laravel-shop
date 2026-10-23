<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_accounts', function (Blueprint $table): void {
            $table->decimal('opening_balance', 20, 2)->default(0);
        });

        Schema::create('finance_internal_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 60)->unique();
            $table->foreignId('from_account_id')->constrained('finance_accounts');
            $table->foreignId('to_account_id')->constrained('finance_accounts');
            $table->decimal('amount', 20, 2);
            $table->date('transfer_date');
            $table->string('description', 255);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['transfer_date', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_internal_transfers');
        Schema::table('finance_accounts', fn (Blueprint $table) => $table->dropColumn('opening_balance'));
    }
};
