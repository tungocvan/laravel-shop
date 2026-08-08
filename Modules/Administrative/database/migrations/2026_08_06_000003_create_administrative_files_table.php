<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained('administrative_submissions')->cascadeOnDelete()->comment('Hồ sơ sở hữu file');
            $table->string('file_type', 30)->comment('Phân loại file hồ sơ hoặc file kết quả');
            $table->string('document_type', 100)->nullable()->comment('Loại giấy tờ nghiệp vụ');
            $table->string('disk', 50)->comment('Storage disk riêng tư');
            $table->string('path', 1024)->comment('Đường dẫn lưu file riêng tư');
            $table->string('original_name')->comment('Tên file do người dùng cung cấp');
            $table->string('stored_name')->unique()->comment('Tên file vật lý duy nhất đã được sinh an toàn');
            $table->string('mime_type', 150)->comment('MIME type đã được hệ thống xác định');
            $table->string('extension', 20)->comment('Phần mở rộng đã xác thực');
            $table->unsignedBigInteger('size')->comment('Dung lượng file theo byte');
            $table->char('checksum', 64)->nullable()->comment('SHA-256 của nội dung file');
            $table->foreignId('uploaded_by_admin_id')->nullable()->constrained('users')->nullOnDelete()->comment('Quản trị viên tải file kết quả');
            $table->timestamps();

            $table->index(['submission_id', 'file_type'], 'administrative_files_submission_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_files');
    }
};
