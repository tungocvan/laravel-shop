<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = ['view_pharma_commercial_policies', 'manage_pharma_commercial_policies'];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) return;
        $now = now();
        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->insertOrIgnore(['name' => $permission, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) DB::table('permissions')->whereIn('name', self::PERMISSIONS)->delete();
    }
};
