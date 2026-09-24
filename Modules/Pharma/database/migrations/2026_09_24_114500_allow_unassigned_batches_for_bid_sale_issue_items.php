<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_issue_items', function (Blueprint $table) {
            $table->string('batch_number',100)->nullable()->change();
            $table->date('expiry_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_issue_items', function (Blueprint $table) {
            $table->string('batch_number',100)->nullable(false)->change();
            $table->date('expiry_date')->nullable(false)->change();
        });
    }
};
