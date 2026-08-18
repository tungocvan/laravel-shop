<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muasamcong_synced_export_profiles', function (Blueprint $table): void {
            $table->json('header_footer')->nullable()->after('decimals');
            $table->string('logo_path')->nullable()->after('header_footer');
            $table->string('signature_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('muasamcong_synced_export_profiles', function (Blueprint $table): void {
            $table->dropColumn(['header_footer', 'logo_path', 'signature_path']);
        });
    }
};
