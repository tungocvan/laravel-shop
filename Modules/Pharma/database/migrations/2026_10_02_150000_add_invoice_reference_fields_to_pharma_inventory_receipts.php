<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_receipts', function (Blueprint $table) {
            $table->string('invoice_symbol', 100)->nullable()->after('invoice_number');
        });

        Schema::table('pharma_inventory_receipt_items', function (Blueprint $table) {
            $table->decimal('invoice_unit_price_ex_vat', 18, 4)->nullable()->after('unit_price_ex_vat');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_receipt_items', function (Blueprint $table) {
            $table->dropColumn('invoice_unit_price_ex_vat');
        });

        Schema::table('pharma_inventory_receipts', function (Blueprint $table) {
            $table->dropColumn('invoice_symbol');
        });
    }
};
