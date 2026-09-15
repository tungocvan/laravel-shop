<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_price_list_export_profiles', function (Blueprint $table): void {
            $table->json('column_groups')->nullable()->after('column_order');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_price_list_export_profiles', function (Blueprint $table): void {
            $table->dropColumn('column_groups');
        });
    }
};
