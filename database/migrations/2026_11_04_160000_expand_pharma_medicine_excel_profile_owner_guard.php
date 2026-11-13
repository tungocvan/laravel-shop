<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pharma is optional. On fresh installations its tables may not exist
        // when application-level migrations run.
        if (! Schema::hasTable('pharma_medicine_excel_profiles')
            || ! Schema::hasColumn('pharma_medicine_excel_profiles', 'owner_guard')) {
            return;
        }

        Schema::table('pharma_medicine_excel_profiles', function (Blueprint $table): void {
            $table->string('owner_guard', 64)->change();
        });
    }

    public function down(): void
    {
        // Do not truncate stored owner guards during rollback.
    }
};
