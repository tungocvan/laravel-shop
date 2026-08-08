<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_pages', function (Blueprint $table) {
            $table->id()->comment('Khóa chính Fanpage nội bộ');
            $table->foreignId('facebook_connection_id')->constrained('facebook_connections')->cascadeOnDelete()->comment('Kết nối Facebook sở hữu Page');
            $table->string('page_id')->comment('ID Fanpage trên Facebook');
            $table->string('page_name')->index()->comment('Tên Fanpage');
            $table->string('page_category')->nullable()->comment('Danh mục Fanpage');
            $table->text('page_picture_url')->nullable()->comment('URL ảnh đại diện Fanpage');
            $table->text('page_access_token')->nullable()->comment('Page Access Token đã mã hóa');
            $table->timestamp('token_expires_at')->nullable()->index()->comment('Thời điểm hết hạn Page Access Token nếu có');
            $table->json('granted_tasks')->nullable()->comment('Danh sách task Page được Meta cấp');
            $table->boolean('is_active')->default(true)->index()->comment('Page có được phép đăng bài hay không');
            $table->boolean('is_default')->default(false)->index()->comment('Page mặc định khi tạo bài');
            $table->timestamp('last_synced_at')->nullable()->comment('Lần đồng bộ Page gần nhất');
            $table->timestamp('last_verified_at')->nullable()->comment('Lần kiểm tra token Page gần nhất');
            $table->string('last_error_code')->nullable()->index()->comment('Mã lỗi token Page gần nhất');
            $table->text('last_error_message')->nullable()->comment('Thông báo lỗi token Page gần nhất');
            $table->timestamps();
            $table->softDeletes()->comment('Thời điểm xóa mềm Fanpage');

            $table->unique(['facebook_connection_id', 'page_id'], 'facebook_pages_connection_page_unique');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `facebook_pages` COMMENT = 'Lưu Fanpage và Page Access Token phục vụ đăng bài'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_pages');
    }
};
