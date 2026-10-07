<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table): void {
            if (! Schema::hasColumn('admission_applications', 'so_so_tiem_chung')) {
                $table->text('so_so_tiem_chung')->nullable()->after('nguoi_lam_don');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('admission_applications', 'so_so_tiem_chung')) {
                $table->dropColumn('so_so_tiem_chung');
            }
        });
    }
};
