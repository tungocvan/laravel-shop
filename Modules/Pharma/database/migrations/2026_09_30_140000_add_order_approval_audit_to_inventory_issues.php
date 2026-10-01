<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_issues', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_at');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->string('rejection_reason', 1000)->nullable()->after('rejected_at');
            $table->index(['status', 'approved_at'], 'ph_inv_issue_approval_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_issues', function (Blueprint $table) {
            $table->dropIndex('ph_inv_issue_approval_idx');
            $table->dropColumn(['approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason']);
        });
    }
};
