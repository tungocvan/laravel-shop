<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('pharma_inventory_receipt_document_settings')) {
            return;
        }

        Schema::table('pharma_inventory_receipt_document_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('pharma_inventory_receipt_document_settings','receiver_label')) {
                $table->string('receiver_label',120)->default('Người nhận hàng')->after('deliverer_label');
            }
            if (! Schema::hasColumn('pharma_inventory_receipt_document_settings','show_invoice_unit_price')) {
                $table->boolean('show_invoice_unit_price')->default(true)->after('show_unit_price');
            }
            if (! Schema::hasColumn('pharma_inventory_receipt_document_settings','show_vat')) {
                $table->boolean('show_vat')->default(true)->after('show_invoice_unit_price');
            }
            if (! Schema::hasColumn('pharma_inventory_receipt_document_settings','show_receiver_signature')) {
                $table->boolean('show_receiver_signature')->default(true)->after('show_deliverer_signature');
            }
        });
    }

    public function down(): void
    {
        // Additive compatibility migration: preserve document-setting data on rollback.
    }
};
