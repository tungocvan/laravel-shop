<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_flash_sale_items', function (Blueprint $table) {

        
                            $table->id();
        
                            // 2.1. Khoa ngoai den bang cha (Flash Sale)
                            $table->foreignId('flash_sale_id')->constrained('wp_flash_sales')->onDelete('cascade');
        
                            // 2.2. Khoa ngoai den bang Products (UPDATE: Trỏ vào wp_products)
                            $table->foreignId('product_id')->constrained('wp_products')->onDelete('cascade');
        
                            // 2.3. Thong tin ban hang
                            $table->decimal('price', 15, 2)->comment('Giá sale cố định');
                            $table->integer('quantity')->default(0)->comment('Số suất sale');
                            $table->integer('sold')->default(0)->comment('Số lượng đã bán');
        
                            // Index composite để query nhanh & đảm bảo 1 sản phẩm chỉ xuất hiện 1 lần trong 1 đợt sale
                            $table->unique(['flash_sale_id', 'product_id'], 'unique_flash_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_flash_sale_items');
    }
};