<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_banners', function (Blueprint $table) {

        
                            $table->id();
        
                            // 1.1. Thong tin co ban
                            $table->string('title')->nullable()->comment('Tiêu đề banner (để quản lý)');
                            $table->string('sub_title')->nullable()->comment('Mô tả phụ dưới tiêu đề');
                            $table->string('image_desktop')->comment('Đường dẫn ảnh Desktop');
                            $table->string('image_mobile')->nullable()->comment('Đường dẫn ảnh Mobile (nếu có)');
                            $table->string('link')->nullable()->comment('Link khi click vào banner');
                            $table->string('btn_text')->nullable()->comment('Chữ trên nút bấm (VD: Mua ngay)');
        
                            // 1.2. Vi tri va Sap xep
                            // Enum position giúp ta tái sử dụng bảng này cho nhiều chỗ khác nhau trên trang chủ
                            $table->string('position')->default('hero')->index()->comment('Vị trí: hero, promo_1, promo_2...');
                            $table->integer('order')->default(0)->comment('Số thứ tự hiển thị (nhỏ xếp trước)');
        
                            // 1.3. Trang thai
                            $table->boolean('is_active')->default(true);
        
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_banners');
    }
};