<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {

        
                            $table->id();
        
                            // 1. Liên kết người dùng (Người đánh giá)
                            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        
                            // 2. Liên kết sản phẩm (Lưu ý: Bảng sản phẩm của bạn tên là 'wp_products')
                            $table->foreignId('product_id')->constrained('wp_products')->cascadeOnDelete();
        
                            // 3. Nội dung đánh giá
                            $table->unsignedTinyInteger('rating')->default(5); // Điểm sao (1-5)
                            $table->text('comment')->nullable(); // Nội dung bình luận
                            $table->json('images')->nullable(); // Cho phép khách upload ảnh Review (Mảng JSON)
        
                            // 4. Trạng thái & Xác thực
                            $table->boolean('is_approved')->default(true); // Kiểm duyệt (True: hiện, False: ẩn)
                            $table->boolean('is_verified_purchase')->default(false); // Đã mua hàng thật chưa? (Tích xanh)
        
                            // 5. Thống kê (Optional - Master UI Feature)
                            $table->integer('likes')->default(0); // Số người thấy hữu ích
        
                            $table->timestamps();
        
                            // Index để query nhanh
                            $table->index(['product_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};