<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_supplier_trackings', function (Blueprint $table) {
            $table->json('distribution_provinces')->nullable()->after('distribution_regions');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_supplier_trackings', function (Blueprint $table) {
            $table->dropColumn('distribution_provinces');
        });
    }
};
