<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_metas', function (Blueprint $table) {
            $table->id()->comment('ID dữ liệu mở rộng của user');

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User sở hữu meta này');

            $table->string('key')->comment('Khóa meta, ví dụ: zalo, facebook, tax_code');
            $table->text('value')->nullable()->comment('Giá trị meta');
            $table->string('group_name')->default('general')->comment('Nhóm meta');
            $table->string('type')->default('text')->comment('Kiểu dữ liệu: text, image, textarea, json');
            $table->string('label')->nullable()->comment('Tên hiển thị của meta');

            $table->timestamps();

            $table->unique(['user_id', 'key']);
            $table->index(['group_name', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_metas');
    }
};
