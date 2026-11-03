<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_issue_document_settings', function (Blueprint $table): void {
            $table->boolean('show_deferred_supply')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_issue_document_settings', function (Blueprint $table): void {
            $table->dropColumn('show_deferred_supply');
        });
    }
};
