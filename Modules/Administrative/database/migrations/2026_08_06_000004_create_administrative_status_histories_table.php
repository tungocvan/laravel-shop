<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained('administrative_submissions')->cascadeOnDelete()->comment('Hồ sơ được ghi lịch sử');
            $table->string('from_status', 30)->nullable()->comment('Trạng thái trước thao tác');
            $table->string('to_status', 30)->comment('Trạng thái sau thao tác');
            $table->string('action', 30)->comment('Hành động nghiệp vụ');
            $table->string('actor_type', 30)->comment('Loại chủ thể thực hiện');
            $table->unsignedBigInteger('actor_id')->nullable()->comment('ID chủ thể nếu có');
            $table->text('note')->nullable()->comment('Nội dung xử lý hoặc phản hồi');
            $table->string('reason_code', 50)->nullable()->comment('Mã lý do từ chối');
            $table->text('reason')->nullable()->comment('Chi tiết lý do từ chối');
            $table->json('metadata')->nullable()->comment('Dữ liệu kiểm toán mở rộng không chứa bí mật');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['submission_id', 'created_at'], 'administrative_histories_submission_date_index');
            $table->index(['actor_type', 'actor_id'], 'administrative_histories_actor_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_status_histories');
    }
};
