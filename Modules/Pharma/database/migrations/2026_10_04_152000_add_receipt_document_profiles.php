<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_receipt_document_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('pharma_inventory_receipt_document_settings','cost_document_title')) {
                $table->string('cost_document_title',120)->default('PHIẾU NHẬP KHO - GIÁ VỐN');
            }
            if (! Schema::hasColumn('pharma_inventory_receipt_document_settings','cost_document_subtitle')) {
                $table->string('cost_document_subtitle')->nullable();
            }
            if (! Schema::hasColumn('pharma_inventory_receipt_document_settings','cost_show_invoice')) {
                $table->boolean('cost_show_invoice')->default(false);
            }
            if (! Schema::hasColumn('pharma_inventory_receipt_document_settings','invoice_document_title')) {
                $table->string('invoice_document_title',120)->default('PHIẾU NHẬP KHO - HÓA ĐƠN');
            }
            if (! Schema::hasColumn('pharma_inventory_receipt_document_settings','invoice_document_subtitle')) {
                $table->string('invoice_document_subtitle')->nullable();
            }
        });

        DB::table('permissions')->updateOrInsert(
            ['name'=>'view_pharma_inventory_costs','guard_name'=>'web'],
            ['updated_at'=>now(),'created_at'=>now()]
        );
    }

    public function down(): void
    {
        // Preserve profile settings and permission assignments on rollback.
    }
};
