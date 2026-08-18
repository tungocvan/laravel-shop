<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muasamcong_synced_export_profiles', function (Blueprint $table): void {
            $table->json('decimals')->nullable()->after('data_types');
        });
    }

    public function down(): void
    {
        Schema::table('muasamcong_synced_export_profiles', function (Blueprint $table): void {
            $table->dropColumn('decimals');
        });
    }
};
