<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('finance_debt_obligations', function (Blueprint $table): void {
            $table->unsignedBigInteger('partner_id')->nullable()->index();
        });
    }

    public function down(): void {
        Schema::table('finance_debt_obligations', function (Blueprint $table): void {
            $table->dropIndex(['partner_id']);
            $table->dropColumn('partner_id');
        });
    }
};
