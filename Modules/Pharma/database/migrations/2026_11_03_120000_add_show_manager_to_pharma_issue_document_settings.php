<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_issue_document_settings', function (Blueprint $table) {
            $table->boolean('show_manager')->default(true)->after('show_price_list');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_issue_document_settings', function (Blueprint $table) {
            $table->dropColumn('show_manager');
        });
    }
};
