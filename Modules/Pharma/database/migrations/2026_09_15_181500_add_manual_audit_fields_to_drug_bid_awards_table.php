<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_drug_bid_awards', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('source_payload_hash')->constrained('users')->nullOnDelete();
            $table->text('manual_note')->nullable()->after('created_by');
            $table->index(['source_type', 'created_by'], 'pharma_bid_awards_source_creator_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_drug_bid_awards', function (Blueprint $table): void {
            $table->dropIndex('pharma_bid_awards_source_creator_idx');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('manual_note');
        });
    }
};
