<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_medicine_excel_profiles', function (Blueprint $table): void {
            $table->string('owner_guard', 16)->default('admin')->after('user_id');
            $table->dropUnique(['user_id', 'name']);
            $table->unique(['owner_guard', 'user_id', 'name'], 'medicine_excel_profiles_guard_user_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_medicine_excel_profiles', function (Blueprint $table): void {
            $table->dropUnique('medicine_excel_profiles_guard_user_name_unique');
            $table->dropColumn('owner_guard');
            $table->unique(['user_id', 'name']);
        });
    }
};
