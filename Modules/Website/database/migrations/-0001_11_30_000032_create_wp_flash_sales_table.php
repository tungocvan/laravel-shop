<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_flash_sales', function (Blueprint $table) {

        
                            $table->id();
        
                            $table->string('title')->comment('Tên chương trình (vd: Sale 9.9)');
                            $table->dateTime('start_time')->comment('Thời gian bắt đầu');
                            $table->dateTime('end_time')->comment('Thời gian kết thúc');
                            $table->boolean('is_active')->default(true);
        
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_flash_sales');
    }
};