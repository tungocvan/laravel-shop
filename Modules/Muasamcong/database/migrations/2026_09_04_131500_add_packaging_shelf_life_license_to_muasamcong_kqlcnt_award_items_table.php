<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muasamcong_kqlcnt_award_items', function (Blueprint $table): void {
            $table->string('packaging_spec')->nullable()->after('dosage_form');
            $table->unsignedInteger('shelf_life_months')->nullable()->after('packaging_spec');
            $table->string('registration_or_import_license')->nullable()->after('shelf_life_months');
        });
    }

    public function down(): void
    {
        Schema::table('muasamcong_kqlcnt_award_items', function (Blueprint $table): void {
            $table->dropColumn([
                'packaging_spec',
                'shelf_life_months',
                'registration_or_import_license',
            ]);
        });
    }
};
