<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('pharma_supplier_trackings')
            ->select('medicine_id', 'supplier_name_normalized', 'working_date', DB::raw('COUNT(*) as duplicate_count'))
            ->whereNotNull('working_date')
            ->whereNotNull('supplier_name_normalized')
            ->groupBy('medicine_id', 'supplier_name_normalized', 'working_date')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new RuntimeException('Supplier Tracking contains duplicate business keys. Resolve duplicates before applying the unique constraint.');
        }

        Schema::table('pharma_supplier_trackings', function (Blueprint $table) {
            $table->unique(
                ['medicine_id', 'supplier_name_normalized', 'working_date'],
                'supplier_trackings_business_key_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pharma_supplier_trackings', function (Blueprint $table) {
            $table->dropUnique('supplier_trackings_business_key_unique');
        });
    }
};
