<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_medicines', function (Blueprint $table) {
            $table->string('therapeutic_group')->nullable()->after('circular_group')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pharma_medicines', function (Blueprint $table) {
            $table->dropIndex(['therapeutic_group']);
            $table->dropColumn('therapeutic_group');
        });
    }
};
