<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_drug_bid_awards', function (Blueprint $table) {
            $table->string('canonical_identity_key', 191)->nullable()->after('id');
            $table->string('lot_no')->nullable()->after('bidding_notice_code');
            $table->string('lot_name')->nullable()->after('lot_no');
            $table->string('medicine_code')->nullable()->after('medicine_id');
            $table->string('active_ingredient')->nullable()->after('medicine_name');
            $table->string('concentration')->nullable()->after('active_ingredient');
            $table->string('route')->nullable()->after('concentration');
            $table->string('dosage_form')->nullable()->after('route');
            $table->string('unit')->nullable()->after('dosage_form');
            $table->string('drug_group')->nullable()->after('unit');
            $table->unsignedInteger('shelf_life_months')->nullable()->after('packaging_specification');
            $table->string('registration_or_import_license')->nullable()->after('shelf_life_months');
            $table->string('manufacturer')->nullable()->after('registration_or_import_license');
            $table->string('country')->nullable()->after('manufacturer');
            $table->decimal('price_plan', 20, 4)->nullable()->after('quantity');
            $table->decimal('winning_price', 20, 4)->nullable()->after('price_plan');
            $table->decimal('amount', 20, 4)->nullable()->after('winning_price');
            $table->string('contractor_code')->nullable()->after('winning_company_name');
            $table->string('investor_code')->nullable()->after('investor_name');
            $table->timestamp('published_at')->nullable()->after('decision_date');
            $table->string('contract_no')->nullable()->after('published_at');
            $table->unsignedInteger('contract_period')->nullable()->after('contract_duration_months');
            $table->string('contract_period_unit', 16)->nullable()->after('contract_period');
            $table->string('contract_period_text')->nullable()->after('contract_period_unit');
            $table->string('effect_frame_period')->nullable()->after('contract_period_text');
            $table->string('medicine_match_status', 32)->default('unresolved')->after('medicine_id');
            $table->boolean('is_active')->default(true)->after('effect_frame_period');

            $table->unique('canonical_identity_key', 'pharma_drug_awards_canonical_identity_unique');
            $table->index(['medicine_id', 'medicine_match_status'], 'pharma_drug_awards_medicine_match_index');
            $table->index(['bidding_notice_code', 'lot_no'], 'pharma_drug_awards_notice_lot_index');
            $table->index(['is_active', 'published_at'], 'pharma_drug_awards_active_published_index');
        });

        DB::table('pharma_drug_bid_awards')
            ->whereNull('winning_price')
            ->update(['winning_price' => DB::raw('unit_price')]);
    }

    public function down(): void
    {
        Schema::table('pharma_drug_bid_awards', function (Blueprint $table) {
            $table->dropUnique('pharma_drug_awards_canonical_identity_unique');
            $table->dropIndex('pharma_drug_awards_medicine_match_index');
            $table->dropIndex('pharma_drug_awards_notice_lot_index');
            $table->dropIndex('pharma_drug_awards_active_published_index');
            $table->dropColumn([
                'canonical_identity_key',
                'lot_no',
                'lot_name',
                'medicine_code',
                'medicine_match_status',
                'active_ingredient',
                'concentration',
                'route',
                'dosage_form',
                'unit',
                'drug_group',
                'shelf_life_months',
                'registration_or_import_license',
                'manufacturer',
                'country',
                'price_plan',
                'winning_price',
                'amount',
                'contractor_code',
                'investor_code',
                'published_at',
                'contract_no',
                'contract_period',
                'contract_period_unit',
                'contract_period_text',
                'effect_frame_period',
                'is_active',
            ]);
        });
    }
};
