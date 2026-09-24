<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pharma_inventory_receipt_document_settings', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name')->nullable();
            $table->string('organization_address',500)->nullable();
            $table->string('tax_code',50)->nullable();
            $table->string('phone',50)->nullable();
            $table->string('document_title',120)->default('PHIẾU NHẬP KHO');
            $table->string('document_subtitle')->nullable();
            $table->string('warehouse_name',120)->default('Kho chính');
            $table->string('issuer_label',120)->default('Người lập phiếu');
            $table->string('deliverer_label',120)->default('Người giao hàng');
            $table->string('keeper_label',120)->default('Thủ kho');
            $table->string('manager_label',120)->default('Người phụ trách');
            $table->text('footer_note')->nullable();
            $table->boolean('show_invoice')->default(true);
            $table->boolean('show_unit_price')->default(true);
            $table->boolean('show_total_value')->default(true);
            $table->boolean('show_notes')->default(true);
            $table->boolean('show_issuer_signature')->default(true);
            $table->boolean('show_deliverer_signature')->default(true);
            $table->boolean('show_keeper_signature')->default(true);
            $table->boolean('show_manager_signature')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_inventory_receipt_document_settings');
    }
};
