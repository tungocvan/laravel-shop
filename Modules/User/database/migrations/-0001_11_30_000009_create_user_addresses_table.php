<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {


                            $table->id();
                            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

                            $table->string('name')->nullable();; // Tên người nhận
                            $table->string('phone')->nullable(); // SĐT người nhận

                            // Địa chỉ chi tiết (Tùy project của bạn có tách Xã/Huyện/Tỉnh không, ở đây tôi làm gộp cho gọn, bạn có thể tách nếu cần)
                            $table->string('address')->nullable();
                            $table->string('city')->nullable();
                            $table->string('district')->nullable();
                            $table->string('ward')->nullable();

                            $table->boolean('is_default')->default(false);
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
