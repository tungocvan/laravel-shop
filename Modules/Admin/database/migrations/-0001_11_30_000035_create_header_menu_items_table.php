<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('header_menu_items', function (Blueprint $table) {

        
                            $table->id();
                            $table->foreignId('header_menu_id')->constrained()->cascadeOnDelete();
                            $table->foreignId('parent_id')->nullable()->constrained('header_menu_items')->cascadeOnDelete();
        
                            $table->string('title');
                            $table->string('url')->nullable();
                            $table->string('route_name')->nullable(); // Dùng route name cho chuẩn Laravel
                            $table->json('params')->nullable(); // Tham số cho route
        
                            $table->string('icon')->nullable(); // Class icon hoặc đường dẫn ảnh
                            $table->string('target')->default('_self'); // _blank, _self
                            $table->integer('sort_order')->default(0);
                            $table->boolean('is_active')->default(true);
        
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('header_menu_items');
    }
};