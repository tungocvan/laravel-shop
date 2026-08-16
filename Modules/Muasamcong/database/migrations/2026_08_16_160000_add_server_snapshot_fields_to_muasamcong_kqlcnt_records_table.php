<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muasamcong_kqlcnt_records', function (Blueprint $table): void {
            $table->boolean('current_contractor_won')->default(false)->after('published');
            $table->json('all_winners')->nullable()->after('contracts');
            $table->string('hsmt_json_path')->nullable()->after('contracts_raw');
            $table->string('hsmt_excel_path')->nullable()->after('hsmt_json_path');
            $table->unsignedInteger('hsmt_total_items')->nullable()->after('hsmt_excel_path');
            $table->string('hsmt_checksum', 64)->nullable()->after('hsmt_total_items');
            $table->timestamp('hsmt_synced_at')->nullable()->after('hsmt_checksum');
        });
    }

    public function down(): void
    {
        Schema::table('muasamcong_kqlcnt_records', function (Blueprint $table): void {
            $table->dropColumn([
                'current_contractor_won',
                'all_winners',
                'hsmt_json_path',
                'hsmt_excel_path',
                'hsmt_total_items',
                'hsmt_checksum',
                'hsmt_synced_at',
            ]);
        });
    }
};
