<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wp_banners', function (Blueprint $table): void {
            $table->dateTime('starts_at')->nullable()->after('is_active')->index();
            $table->dateTime('ends_at')->nullable()->after('starts_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('wp_banners', function (Blueprint $table): void {
            $table->dropIndex(['starts_at']);
            $table->dropIndex(['ends_at']);
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
