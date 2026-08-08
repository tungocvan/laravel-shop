<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_connections', function (Blueprint $table) {
            $table->id()->comment('Khóa chính kết nối Facebook');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('ID quản trị viên tạo kết nối');
            $table->string('facebook_user_id')->nullable()->index()->comment('ID người dùng Facebook đã cấp quyền');
            $table->string('facebook_user_name')->nullable()->comment('Tên người dùng Facebook đã cấp quyền');
            $table->text('user_access_token')->nullable()->comment('User Access Token đã mã hóa');
            $table->string('token_type')->nullable()->comment('Loại token Meta trả về');
            $table->timestamp('token_expires_at')->nullable()->index()->comment('Thời điểm hết hạn User Access Token nếu có');
            $table->json('granted_scopes')->nullable()->comment('Danh sách quyền đã được cấp');
            $table->json('declined_scopes')->nullable()->comment('Danh sách quyền bị từ chối');
            $table->string('status')->default('active')->index()->comment('Trạng thái: active, invalid, disconnected');
            $table->timestamp('last_verified_at')->nullable()->comment('Lần kiểm tra token gần nhất');
            $table->string('last_error_code')->nullable()->index()->comment('Mã lỗi Meta gần nhất');
            $table->text('last_error_message')->nullable()->comment('Thông báo lỗi an toàn gần nhất');
            $table->timestamps();
            $table->softDeletes()->comment('Thời điểm xóa mềm kết nối');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `facebook_connections` COMMENT = 'Lưu kết nối OAuth Facebook của quản trị viên'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_connections');
    }
};
