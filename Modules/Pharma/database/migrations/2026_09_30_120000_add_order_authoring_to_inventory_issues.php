<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_issues', function (Blueprint $table) {
            $table->foreignId('recipient_partner_id')->nullable()->after('recipient_name')
                ->constrained('partners')->nullOnDelete();
            $table->unsignedBigInteger('submitted_by')->nullable()->after('created_by');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->index(['status', 'submitted_at'], 'ph_inv_issue_submit_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_issues', function (Blueprint $table) {
            $table->dropIndex('ph_inv_issue_submit_idx');
            $table->dropColumn(['submitted_by', 'submitted_at']);
            $table->dropConstrainedForeignId('recipient_partner_id');
        });
    }
};
