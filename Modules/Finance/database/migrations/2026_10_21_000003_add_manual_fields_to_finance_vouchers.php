<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_vouchers', function (Blueprint $table): void {
            $table->string('origin', 20)->default('source');
            $table->string('manual_category', 60)->nullable();
            $table->string('counterparty')->nullable();
            $table->text('description')->nullable();
            $table->string('reference_number', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('finance_vouchers', function (Blueprint $table): void {
            $table->dropColumn(['origin', 'manual_category', 'counterparty', 'description', 'reference_number']);
        });
    }
};
