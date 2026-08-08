<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_orders', function (Blueprint $table) {

        
                            $table->id();
                            // Liên kết người mua (nếu đã đăng nhập)
                            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        
                            // --- PHẦN AFFILIATE (MỚI THÊM) ---
                            // Người giới thiệu (Lấy từ bảng users)
                            $table->foreignId('affiliate_id')->nullable()->constrained('users')->nullOnDelete();
                            // Trạng thái hoa hồng: pending (chờ), approved (duyệt), rejected (hủy)
                            $table->string('commission_status')->default('pending')->index();
                            $table->text('rejection_reason')->nullable();
                            // Số tiền hoa hồng dự kiến (VD: 10% đơn hàng)
                            $table->decimal('commission_amount', 15, 2)->default(0);
                            // ----------------------------------
        
                            $table->string('order_code')->unique(); // VD: ORD-20231001-001
        
                            // Thông tin khách hàng (Snapshot)
                            $table->string('customer_name');
                            $table->string('customer_phone');
                            $table->string('customer_email')->nullable();
                            $table->string('customer_address');
                            $table->text('note')->nullable();
        
                            // Tiền tệ
                            $table->decimal('subtotal', 15, 2);      // Tổng tiền hàng
                            $table->decimal('shipping_fee', 15, 2)->default(0); // Phí ship
                            $table->decimal('discount', 15, 2)->default(0);     // Giảm giá
                            $table->decimal('total', 15, 2);         // Tổng thanh toán
        
                            // Trạng thái
                            $table->string('payment_method')->default('cod'); // cod, momo, vnpay
                            $table->string('status')->default('pending')->index(); // pending, processing, shipping, completed, cancelled
        
        
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_orders');
    }
};