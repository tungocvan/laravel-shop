<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings') || ! Schema::hasTable('wp_settings')) {
            return;
        }

        DB::table('wp_settings')
            ->orderBy('id')
            ->chunkById(100, function ($legacySettings): void {
                foreach ($legacySettings as $legacy) {
                    if (DB::table('settings')->where('key', $legacy->key)->exists()) {
                        continue;
                    }

                    DB::table('settings')->insert([
                        'key' => $legacy->key,
                        'value' => $legacy->value,
                        'group_name' => $legacy->group_name,
                        'type' => $legacy->type,
                        'label' => $legacy->label,
                        'created_at' => $legacy->created_at,
                        'updated_at' => $legacy->updated_at,
                    ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        // Intentionally non-destructive. The legacy table remains untouched and
        // rows copied into canonical settings may have received production writes.
    }
};
