<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muasamcong_synced_export_preferences', function (Blueprint $table): void {
            $table->json('data_types')->nullable()->after('widths');
        });

        DB::table('muasamcong_synced_export_preferences')
            ->select(['id', 'widths'])
            ->orderBy('id')
            ->get()
            ->each(function (object $row): void {
                $widths = is_string($row->widths) ? json_decode($row->widths, true) : $row->widths;
                if (! is_array($widths)) {
                    return;
                }

                $changed = false;
                foreach ($widths as $key => $width) {
                    if (! is_numeric($width)) {
                        continue;
                    }

                    $numericWidth = (float) $width;
                    if ($numericWidth <= 80) {
                        $widths[$key] = (int) round(max(40, min(600, ($numericWidth * 7) + 5)));
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('muasamcong_synced_export_preferences')
                        ->where('id', $row->id)
                        ->update(['widths' => json_encode($widths, JSON_UNESCAPED_UNICODE)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('muasamcong_synced_export_preferences', function (Blueprint $table): void {
            $table->dropColumn('data_types');
        });
    }
};
