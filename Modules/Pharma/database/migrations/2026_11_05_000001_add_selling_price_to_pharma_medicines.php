<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_medicines', function (Blueprint $table): void {
            $table->decimal('selling_price', 15, 2)->nullable()->after('declared_price');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_medicines', function (Blueprint $table): void {
            $table->dropColumn('selling_price');
        });
    }
};
