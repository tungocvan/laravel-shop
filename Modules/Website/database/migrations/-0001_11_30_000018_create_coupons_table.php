<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {

        
                            $table->id();
                            $table->string('code')->unique(); // Mã giảm giá (VD: SALE50)
                            $table->string('description')->nullable(); // Mô tả
        
                            $table->enum('type', ['percent', 'fixed'])->default('fixed'); // Loại: Phần trăm hoặc Tiền mặt
                            $table->decimal('value', 15, 2); // Giá trị giảm
        
                            $table->decimal('min_order_value', 15, 2)->default(0); // Giá trị đơn hàng tối thiểu
                            $table->integer('usage_limit')->nullable(); // Giới hạn số lần dùng chung
                            $table->integer('usage_count')->default(0); // Đã dùng bao nhiêu lần
        
                            $table->timestamp('starts_at')->nullable(); // Ngày bắt đầu
                            $table->timestamp('expires_at')->nullable(); // Ngày hết hạn
        
                            $table->boolean('is_active')->default(true);
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};