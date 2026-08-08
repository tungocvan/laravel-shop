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
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id()->comment('ID hồ sơ khách hàng');

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Liên kết với tài khoản đăng nhập');

            $table->string('customer_code')->nullable()->unique()->comment('Mã khách hàng');
            $table->string('gender', 20)->nullable()->comment('Giới tính');
            $table->date('birthday')->nullable()->comment('Ngày sinh');

            $table->string('address')->nullable()->comment('Địa chỉ');
            $table->string('province')->nullable()->comment('Tỉnh/thành phố');
            $table->string('district')->nullable()->comment('Quận/huyện');
            $table->string('ward')->nullable()->comment('Phường/xã');

            $table->string('status', 30)
                ->default('active')
                ->comment('Trạng thái khách hàng: active, inactive, blocked');

            $table->text('note')->nullable()->comment('Ghi chú về khách hàng');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_code', 'status']);
            $table->index(['province', 'district', 'ward']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
