<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_medicine_award_preferences', function (Blueprint $table): void {
            $table->string('tender_validity_text', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pharma_medicine_award_preferences', function (Blueprint $table): void {
            $table->dropColumn('tender_validity_text');
        });
    }
};
