<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_procedures', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã nghiệp vụ của thủ tục hành chính');
            $table->string('name')->comment('Tên thủ tục hành chính');
            $table->string('slug')->unique()->comment('Định danh dùng trên URL công khai');
            $table->text('description')->nullable()->comment('Mô tả ngắn của thủ tục');
            $table->longText('instructions')->nullable()->comment('Hướng dẫn thực hiện');
            $table->json('required_documents')->nullable()->comment('Danh sách giấy tờ cần nộp');
            $table->string('template_disk', 50)->nullable()->comment('Storage disk chứa biểu mẫu');
            $table->string('template_path', 1024)->nullable()->comment('Đường dẫn riêng tư của biểu mẫu');
            $table->string('template_original_name')->nullable()->comment('Tên gốc của biểu mẫu');
            $table->json('allowed_extensions')->nullable()->comment('Các phần mở rộng file được chấp nhận');
            $table->unsignedInteger('max_file_size_kb')->default(10240)->comment('Dung lượng tối đa của mỗi file theo KB');
            $table->unsignedSmallInteger('max_files')->default(5)->comment('Số file tối đa trong một hồ sơ');
            $table->boolean('is_active')->default(true)->index()->comment('Trạng thái công khai của thủ tục');
            $table->unsignedInteger('sort_order')->default(0)->comment('Thứ tự hiển thị');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('Quản trị viên tạo thủ tục');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('Quản trị viên cập nhật gần nhất');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order'], 'administrative_procedures_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_procedures');
    }
};
