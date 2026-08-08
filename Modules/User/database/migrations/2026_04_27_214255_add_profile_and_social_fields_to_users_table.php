<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // ===== PROFILE =====
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');

            // ===== SYSTEM =====
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            // ===== GOOGLE =====
            $table->string('google_id')->nullable()->unique()->after('last_login_at');
            $table->text('google_token')->nullable()->after('google_id');
            $table->string('google_refresh_token')->nullable()->after('google_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'avatar',
                'is_active',
                'last_login_at',
                'google_id',
                'google_token',
                'google_refresh_token',
            ]);
        });
    }
};