<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_drug_bid_awards', function (Blueprint $table) {
            $table->dropUnique('unique_bid_award_item');

            $table->string('packaging_specification')->nullable()->change();
            $table->decimal('quantity', 20, 4)->unsigned()->nullable()->change();
            $table->decimal('unit_price', 15, 2)->nullable()->change();
            $table->string('bidding_notice_code')->nullable()->change();
            $table->string('investor_name')->nullable()->change();
            $table->string('decision_number')->nullable()->change();
            $table->date('decision_date')->nullable()->change();
            $table->integer('contract_duration_months')->unsigned()->nullable()->change();
            $table->string('winning_company_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pharma_drug_bid_awards', function (Blueprint $table) {
            $table->unique(
                ['bidding_notice_code', 'medicine_name', 'winning_company_name'],
                'unique_bid_award_item'
            );
        });
    }
};
