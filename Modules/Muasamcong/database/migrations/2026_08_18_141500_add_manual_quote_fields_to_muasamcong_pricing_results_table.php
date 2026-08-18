<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muasamcong_pricing_results', function (Blueprint $table): void {
            $table->string('stt_tt20_2022', 100)->nullable()->after('gdklh_gpnk');
            $table->decimal('gia_kk_kkl', 20, 4)->nullable()->after('stt_tt20_2022');
            $table->decimal('don_gia_vat', 20, 4)->nullable()->after('gia_kk_kkl');
        });
    }

    public function down(): void
    {
        Schema::table('muasamcong_pricing_results', function (Blueprint $table): void {
            $table->dropColumn(['stt_tt20_2022', 'gia_kk_kkl', 'don_gia_vat']);
        });
    }
};
