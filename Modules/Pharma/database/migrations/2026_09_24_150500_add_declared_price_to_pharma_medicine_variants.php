<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_medicine_variants', function (Blueprint $table): void {
            $table->decimal('declared_price', 18, 2)->nullable()->after('base_unit');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_medicine_variants', function (Blueprint $table): void {
            $table->dropColumn('declared_price');
        });
    }
};
