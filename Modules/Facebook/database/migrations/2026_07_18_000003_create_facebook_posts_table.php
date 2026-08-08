<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_posts', function (Blueprint $table) {
            $table->id()->comment('Khóa chính bài đăng Facebook');
            $table->foreignId('facebook_page_id')->constrained('facebook_pages')->cascadeOnDelete()->comment('Fanpage sẽ đăng bài');
            $table->unsignedBigInteger('created_by')->nullable()->index()->comment('ID quản trị viên tạo bài');
            $table->string('title')->nullable()->index()->comment('Tiêu đề nội bộ để quản trị');
            $table->longText('message')->nullable()->comment('Nội dung caption hoặc nội dung bài viết');
            $table->string('post_type')->default('text')->index()->comment('Loại bài: text, photo, link');
            $table->text('link_url')->nullable()->comment('URL dùng cho bài đăng dạng link');
            $table->string('status')->default('draft')->index()->comment('Trạng thái: draft, scheduled, queued, processing, published, failed, cancelled');
            $table->timestamp('scheduled_at')->nullable()->index()->comment('Thời điểm dự kiến đăng');
            $table->timestamp('queued_at')->nullable()->comment('Thời điểm đưa vào queue');
            $table->timestamp('processing_at')->nullable()->comment('Thời điểm worker bắt đầu xử lý');
            $table->timestamp('published_at')->nullable()->index()->comment('Thời điểm đăng thành công');
            $table->timestamp('failed_at')->nullable()->index()->comment('Thời điểm đăng thất bại');
            $table->string('facebook_post_id')->nullable()->index()->comment('ID bài đăng Meta trả về');
            $table->text('facebook_permalink')->nullable()->comment('Permalink bài đăng nếu lấy được');
            $table->unsignedInteger('attempts')->default(0)->comment('Số lần thử đăng');
            $table->string('idempotency_key')->nullable()->unique()->comment('Khóa chống tạo bài trùng trong hệ thống');
            $table->string('last_error_code')->nullable()->index()->comment('Mã lỗi Meta gần nhất');
            $table->string('last_error_subcode')->nullable()->comment('Mã lỗi phụ Meta gần nhất');
            $table->string('last_error_type')->nullable()->comment('Loại lỗi Meta gần nhất');
            $table->text('last_error_message')->nullable()->comment('Thông báo lỗi an toàn gần nhất');
            $table->string('last_error_trace_id')->nullable()->comment('fbtrace_id Meta gần nhất');
            $table->json('meta_response')->nullable()->comment('Response Meta đã loại bỏ dữ liệu nhạy cảm');
            $table->timestamps();
            $table->softDeletes()->comment('Thời điểm xóa mềm bài đăng');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `facebook_posts` COMMENT = 'Quản lý bài đăng Facebook Fanpage'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_posts');
    }
};
