<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pharma_medicines') || ! Schema::hasColumn('pharma_medicines', 'medicine_code')) {
            return;
        }

        DB::table('pharma_medicines')
            ->select('id', 'medicine_code')
            ->where(function ($query): void {
                $query->whereNull('medicine_code')->orWhere('medicine_code', '');
            })
            ->orderBy('id')
            ->chunkById(500, function ($medicines): void {
                foreach ($medicines as $medicine) {
                    $code = sprintf('MED-%06d', $medicine->id);

                    $codeAlreadyBelongsToAnotherMedicine = DB::table('pharma_medicines')
                        ->where('medicine_code', $code)
                        ->where('id', '!=', $medicine->id)
                        ->exists();

                    if ($codeAlreadyBelongsToAnotherMedicine) {
                        continue;
                    }

                    DB::table('pharma_medicines')
                        ->where('id', $medicine->id)
                        ->where(function ($query): void {
                            $query->whereNull('medicine_code')->orWhere('medicine_code', '');
                        })
                        ->update(['medicine_code' => $code]);
                }
            }, 'id');
    }

    public function down(): void
    {
        // Medicine codes are canonical identifiers. Never erase generated codes on rollback.
    }
};
