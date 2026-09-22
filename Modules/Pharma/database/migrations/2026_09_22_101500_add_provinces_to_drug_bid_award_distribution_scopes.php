<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_drug_bid_award_distribution_scope_provinces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distribution_scope_id');
            $table->string('province_name', 100);
            $table->timestamps();
            $table->foreign('distribution_scope_id', 'drug_award_scope_province_scope_fk')
                ->references('id')->on('pharma_drug_bid_award_distribution_scopes')->cascadeOnDelete();
            $table->unique(['distribution_scope_id', 'province_name'], 'drug_award_scope_province_unique');
        });

        DB::table('pharma_drug_bid_award_distribution_scopes')
            ->whereNotNull('province_code')
            ->where('province_code', '!=', '')
            ->orderBy('id')
            ->each(function ($scope): void {
                DB::table('pharma_drug_bid_award_distribution_scope_provinces')->insertOrIgnore([
                    'distribution_scope_id' => $scope->id,
                    'province_name' => $scope->province_code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_drug_bid_award_distribution_scope_provinces');
    }
};
