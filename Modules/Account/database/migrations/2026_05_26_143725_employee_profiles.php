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
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id()->comment('ID hồ sơ nhân viên');

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Liên kết với tài khoản đăng nhập');

            $table->string('employee_code')->nullable()->unique()->comment('Mã nhân viên');
            $table->string('department')->nullable()->comment('Phòng ban');
            $table->string('position')->nullable()->comment('Chức vụ');
            $table->date('joined_date')->nullable()->comment('Ngày bắt đầu làm việc');

            $table->string('work_phone')->nullable()->comment('Số điện thoại công việc');
            $table->string('work_email')->nullable()->comment('Email công việc');

            $table->string('status', 30)
                ->default('active')
                ->comment('Trạng thái nhân viên: active, inactive, resigned');

            $table->text('note')->nullable()->comment('Ghi chú nội bộ');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
