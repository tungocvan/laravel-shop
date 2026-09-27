<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_price_list_export_shares', function (Blueprint $table): void {
            $table->text('token_encrypted')->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_price_list_export_shares', function (Blueprint $table): void {
            $table->dropColumn('token_encrypted');
        });
    }
};
