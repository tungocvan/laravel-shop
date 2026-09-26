<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_drug_bid_award_allocations', function (Blueprint $table) {
            $table->decimal('commercial_policy_percentage', 7, 4)->nullable()->after('allocated_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_drug_bid_award_allocations', function (Blueprint $table) {
            $table->dropColumn('commercial_policy_percentage');
        });
    }
};
