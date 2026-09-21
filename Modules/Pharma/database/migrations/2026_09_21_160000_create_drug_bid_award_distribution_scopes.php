<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_drug_bid_award_distribution_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('result_key')->unique();
            $table->string('bidding_notice_code')->nullable()->index();
            $table->string('province_code', 50)->nullable()->index();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('pharma_drug_bid_award_distribution_scope_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_scope_id')->constrained('pharma_drug_bid_award_distribution_scopes')->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['distribution_scope_id', 'partner_id'], 'drug_award_scope_partner_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_drug_bid_award_distribution_scope_partners');
        Schema::dropIfExists('pharma_drug_bid_award_distribution_scopes');
    }
};
