<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {

        
                            $table->id();
                            $table->foreignId('order_id')->constrained('wp_orders')->cascadeOnDelete();
                            $table->foreignId('product_id')->nullable()->constrained('wp_products')->nullOnDelete();
        
                            // Snapshot sản phẩm
                            $table->string('product_name');
                            $table->decimal('price', 15, 2);
                            $table->integer('quantity');
                            $table->decimal('total', 15, 2); // price * quantity
        
                             // Tỷ lệ % tại thời điểm mua
                             $table->decimal('commission_rate', 5, 2)->nullable();
                             // Số tiền hoa hồng thực tế cho item này
                             $table->decimal('commission_amount', 15, 2)->default(0);
                             $table->decimal('commission_fixed_amount', 15, 2)->default(0);
                             $table->string('affiliate_level_snapshot')->nullable();
        
                            $table->json('options')->nullable(); // <--- MỚI: Lưu Size, Màu (JSON)
        
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};