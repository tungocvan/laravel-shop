<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_locations', function (Blueprint $table) {
            $table->id();
            $table->string('province_code')->index(); // Index để tìm kiếm nhanh hơn ⚡
            $table->string('province_name');
            $table->string('ward_code')->unique(); // Mỗi phường xã thường có mã duy nhất 🆔
            $table->string('ward_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_locations');
    }
};