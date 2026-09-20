<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->string('customer_source', 30)->nullable()->after('type')->index();
            $table->foreignId('official_facility_id')->nullable()->after('partner_id')->constrained('pharma_official_source_facilities')->nullOnDelete();
            $table->index(['customer_source', 'official_facility_id'], 'pharma_price_lists_customer_facility_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->dropIndex('pharma_price_lists_customer_facility_idx');
            $table->dropConstrainedForeignId('official_facility_id');
            $table->dropColumn('customer_source');
        });
    }
};
