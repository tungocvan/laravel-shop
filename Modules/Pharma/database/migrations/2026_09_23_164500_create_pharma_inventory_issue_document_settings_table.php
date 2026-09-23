<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pharma_inventory_issue_document_settings', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name')->nullable();
            $table->string('organization_address')->nullable();
            $table->string('tax_code',50)->nullable();
            $table->string('phone',50)->nullable();
            $table->string('document_title')->default('PHIẾU XUẤT KHO');
            $table->string('document_subtitle')->nullable();
            $table->string('warehouse_name')->default('Kho chính');
            $table->string('issuer_label')->default('Người lập phiếu');
            $table->string('deliverer_label')->default('Người giao hàng');
            $table->string('receiver_label')->default('Người nhận hàng');
            $table->text('footer_note')->nullable();
            $table->boolean('show_price_list')->default(true);
            $table->boolean('show_unit_price')->default(true);
            $table->boolean('show_total_value')->default(true);
            $table->boolean('show_notes')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_inventory_issue_document_settings');
    }
};
