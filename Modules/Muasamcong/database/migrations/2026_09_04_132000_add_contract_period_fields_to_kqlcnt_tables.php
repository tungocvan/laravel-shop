<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muasamcong_kqlcnt_records', function (Blueprint $table): void {
            $table->unsignedInteger('contract_period')->nullable()->after('bid_id');
            $table->string('contract_period_unit', 20)->nullable()->after('contract_period');
            $table->string('contract_period_text', 500)->nullable()->after('contract_period_unit');
            $table->string('effect_frame_period', 1000)->nullable()->after('contract_period_text');
        });

        Schema::table('muasamcong_kqlcnt_award_items', function (Blueprint $table): void {
            $table->unsignedInteger('contract_period')->nullable()->after('contract_no');
            $table->string('contract_period_unit', 20)->nullable()->after('contract_period');
            $table->string('contract_period_text', 500)->nullable()->after('contract_period_unit');
            $table->string('effect_frame_period', 1000)->nullable()->after('contract_period_text');
        });
    }

    public function down(): void
    {
        Schema::table('muasamcong_kqlcnt_award_items', function (Blueprint $table): void {
            $table->dropColumn(['contract_period', 'contract_period_unit', 'contract_period_text', 'effect_frame_period']);
        });

        Schema::table('muasamcong_kqlcnt_records', function (Blueprint $table): void {
            $table->dropColumn(['contract_period', 'contract_period_unit', 'contract_period_text', 'effect_frame_period']);
        });
    }
};
