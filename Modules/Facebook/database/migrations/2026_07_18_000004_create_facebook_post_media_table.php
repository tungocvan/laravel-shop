<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_post_media', function (Blueprint $table) {
            $table->id()->comment('Khóa chính media bài đăng');
            $table->foreignId('facebook_post_id')->constrained('facebook_posts')->cascadeOnDelete()->comment('Bài đăng sở hữu media');
            $table->string('media_type')->default('photo')->index()->comment('Loại media: photo');
            $table->string('disk')->default('local')->comment('Disk Laravel Storage lưu ảnh');
            $table->string('path')->comment('Đường dẫn ảnh trong Storage');
            $table->string('original_name')->nullable()->comment('Tên file gốc đã lọc an toàn');
            $table->string('mime_type')->nullable()->comment('MIME type của ảnh');
            $table->unsignedBigInteger('size')->nullable()->comment('Dung lượng ảnh byte');
            $table->unsignedInteger('sort_order')->default(0)->index()->comment('Thứ tự ảnh trong bài');
            $table->string('facebook_media_id')->nullable()->index()->comment('ID media Meta trả về');
            $table->string('status')->default('pending')->index()->comment('Trạng thái media: pending, uploaded, failed');
            $table->text('last_error_message')->nullable()->comment('Lỗi upload media gần nhất');
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `facebook_post_media` COMMENT = 'Lưu media ảnh cho bài đăng Facebook'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_post_media');
    }
};
