<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('website_pharma_quick_order_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('inventory_issue_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('website_pharma_quick_order_requests', function (Blueprint $table): void {
            $table->dropUnique(['inventory_issue_id']);
            $table->dropColumn('inventory_issue_id');
        });
    }
};
