<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasTable('invoices')) {
            return;
        }

        Schema::create('invoices', function (Blueprint $table) {
            $table->id()->comment('Khóa chính hóa đơn');

            $table->string('lookup_code')->nullable()->comment('Mã tra cứu hóa đơn điện tử');
            $table->string('symbol')->nullable()->comment('Ký hiệu mẫu số và ký hiệu hóa đơn');
            $table->string('invoice_number')->nullable()->comment('Số hóa đơn');
            $table->string('type')->nullable()->comment('Tên loại hóa đơn từ GDT');
            $table->date('issued_date')->nullable()->comment('Ngày lập hóa đơn');

            $table->string('tax_code')->nullable()->comment('Mã số thuế của đối tác');
            $table->string('name')->nullable()->comment('Tên người mua hoặc người bán');
            $table->string('address')->nullable()->comment('Địa chỉ của đối tác');
            $table->string('email')->nullable()->comment('Email liên hệ của đối tác');
            $table->string('phone')->nullable()->comment('Số điện thoại của đối tác');

            $table->decimal('tax_rate', 5, 2)->nullable()->comment('Thuế suất VAT theo phần trăm');
            $table->decimal('vat_amount', 18, 2)->nullable()->comment('Số tiền thuế VAT');
            $table->decimal('amount_before_vat', 18, 2)->nullable()->comment('Giá trị trước thuế VAT');
            $table->decimal('total_amount', 18, 2)->nullable()->comment('Tổng tiền thanh toán');

            $table->enum('invoice_type', ['sold', 'purchase'])->default('sold')
                ->comment('Chiều hóa đơn: sold bán ra, purchase mua vào');

            $table->timestamps();

            $table->index(['invoice_type', 'issued_date']);
            $table->index('tax_code');
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
