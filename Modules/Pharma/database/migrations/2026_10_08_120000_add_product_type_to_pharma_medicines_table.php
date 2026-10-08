<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_medicines', function (Blueprint $table): void {
            $table->string('product_type', 32)->default('tan_duoc');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_medicines', function (Blueprint $table): void {
            $table->dropColumn('product_type');
        });
    }
};
