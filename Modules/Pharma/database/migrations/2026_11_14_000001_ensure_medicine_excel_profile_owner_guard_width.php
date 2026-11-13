<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The core migration can run before an optional Pharma module is enabled.
        // Enforce the final column width whenever Pharma migrations are installed.
        Schema::table('pharma_medicine_excel_profiles', function (Blueprint $table): void {
            $table->string('owner_guard', 64)->change();
        });
    }

    public function down(): void
    {
        // A narrower guard would truncate valid web-inventory identifiers.
    }
};
