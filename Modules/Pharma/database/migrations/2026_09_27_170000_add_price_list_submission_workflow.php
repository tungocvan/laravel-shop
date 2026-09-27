<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->foreignId('submitted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->index(['status', 'submitted_at'], 'pharma_price_lists_approval_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->dropIndex('pharma_price_lists_approval_queue_idx');
            $table->dropColumn('submitted_at');
            $table->dropConstrainedForeignId('submitted_by');
        });
    }
};
