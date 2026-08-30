<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pharma_supplier_trackings', 'supplier_name_normalized')) {
            Schema::table('pharma_supplier_trackings', function (Blueprint $table) {
                $table->string('supplier_name_normalized')->nullable()->after('supplier_name');
            });
        }

        DB::table('pharma_supplier_trackings')
            ->select('id', 'supplier_name')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('pharma_supplier_trackings')
                        ->where('id', $row->id)
                        ->update([
                            'supplier_name_normalized' => $this->normalizeSupplierName($row->supplier_name),
                        ]);
                }
            });

        $duplicates = DB::table('pharma_supplier_trackings')
            ->select('medicine_id', 'supplier_name_normalized', 'working_date', DB::raw('COUNT(*) as duplicate_count'))
            ->whereNotNull('working_date')
            ->whereNotNull('supplier_name_normalized')
            ->groupBy('medicine_id', 'supplier_name_normalized', 'working_date')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new \RuntimeException(
                'Supplier Tracking contains duplicate business keys. Resolve duplicate Medicine + Supplier + Working Date records before retrying this migration.'
            );
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
        if (! Schema::hasColumn('pharma_supplier_trackings', 'supplier_name_normalized')) {
            return;
        }

        Schema::table('pharma_supplier_trackings', function (Blueprint $table) {
            $table->dropUnique('supplier_trackings_business_key_unique');
            $table->dropColumn('supplier_name_normalized');
        });
    }

    private function normalizeSupplierName(?string $name): ?string
    {
        $normalized = Str::of((string) $name)->trim()->squish()->lower()->toString();

        return $normalized === '' ? null : $normalized;
    }
};
