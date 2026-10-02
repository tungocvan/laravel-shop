<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('submitted_by')->nullable()->after('created_by');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_at');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_receipts', function (Blueprint $table) {
            $table->dropColumn(['submitted_by', 'submitted_at', 'approved_by', 'approved_at']);
        });
    }
};
