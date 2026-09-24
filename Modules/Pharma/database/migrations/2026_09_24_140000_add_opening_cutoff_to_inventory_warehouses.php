<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_warehouses', function (Blueprint $table) {
            $table->timestamp('opening_cutoff_at')->nullable()->after('is_active');
        });

        DB::table('pharma_inventory_warehouses')->orderBy('id')->each(function ($warehouse): void {
            $cutoff=DB::table('pharma_inventory_transactions')
                ->where('warehouse_id',$warehouse->id)->where('type','opening')->min('created_at');
            if($cutoff) DB::table('pharma_inventory_warehouses')->where('id',$warehouse->id)->update(['opening_cutoff_at'=>$cutoff]);
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_warehouses', fn (Blueprint $table) => $table->dropColumn('opening_cutoff_at'));
    }
};
