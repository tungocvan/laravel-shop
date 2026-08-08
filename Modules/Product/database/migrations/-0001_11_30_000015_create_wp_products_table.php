<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_products', function (Blueprint $table) {


                            $table->id();
                            $table->string('title')->index();
                            $table->string('slug')->unique();

                            $table->text('short_description')->nullable();
                            $table->longText('description')->nullable();

                            // Giá cả
                            $table->decimal('regular_price', 15, 2)->nullable(); // Nên để 15 số để hỗ trợ tiền VNĐ lớn
                            $table->decimal('sale_price', 15, 2)->nullable();

                            // Quản lý kho hàng (Cái bạn đang thiếu)
                            $table->integer('quantity')->default(0);
                            $table->integer('sold_count')->default(0); // Đếm số đã bán (để làm mục Best Sellers)

                            // Ảnh
                            $table->string('image')->nullable(); // Ảnh đại diện
                            $table->json('gallery')->nullable(); // Album ảnh

                            // SEO & Phân loại
                            $table->json('tags')->nullable();
                            $table->boolean('is_active')->default(true)->index();
                            $table->boolean('is_featured')->default(false); // Sản phẩm nổi bật

                            // Người đăng (Để fix lỗi user relationship trước đó)
                            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                            // Thống kê
                            $table->integer('views')->default(0); // Đếm lượt xem
                            $table->decimal('affiliate_commission_rate', 5, 2)->nullable();
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_products');
    }
};
