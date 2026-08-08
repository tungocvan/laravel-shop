<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // Ví dụ: 'ethnicity', 'religion', 'class_type'
            $table->string('code')->nullable(); // Mã danh mục (nếu có)
            $table->string('value'); // Tên hiển thị (Kinh, Phật Giáo,...)
            $table->integer('sort_order')->default(0); // Để sắp xếp thứ tự hiển thị
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['type', 'is_active']); // Tối ưu truy vấn khi lọc theo loại
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_catalogs');
    }
};