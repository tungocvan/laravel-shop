<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_affiliate_schemes', function (Blueprint $table) {

        
                            $table->id();
                            // Khóa ngoại liên kết
                            $table->foreignId('product_id')->constrained('wp_products')->cascadeOnDelete();
                            $table->foreignId('level_id')->nullable()->constrained('affiliate_levels')->nullOnDelete();
                            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Override cho cá nhân
        
                            // Kiểu tính toán
                            $table->enum('commission_type', ['percentage', 'fixed', 'hybrid'])->default('percentage');
        
                            // Giá trị
                            $table->decimal('percent_value', 5, 2)->default(0); // Ví dụ: 10.50 (%)
                            $table->decimal('fixed_value', 15, 2)->default(0);  // Ví dụ: 50000 (đ)
        
                            $table->boolean('is_active')->default(true);
                            $table->timestamps();
        
                            // Đảm bảo không trùng lặp cấu hình cho cùng 1 cấp/user trên 1 sản phẩm
                            $table->unique(['product_id', 'level_id', 'user_id'], 'unique_scheme_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_affiliate_schemes');
    }
};