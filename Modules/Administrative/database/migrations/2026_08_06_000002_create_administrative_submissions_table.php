<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procedure_id')->constrained('administrative_procedures')->restrictOnDelete()->comment('Thủ tục được yêu cầu');
            $table->string('submission_code', 32)->unique()->comment('Mã hồ sơ công khai');
            $table->string('lookup_token_hash', 255)->comment('Hash của mã tra cứu bí mật');
            $table->string('applicant_name')->comment('Họ tên người nộp hồ sơ');
            $table->string('phone', 30)->comment('Số điện thoại liên hệ');
            $table->string('email')->nullable()->comment('Email liên hệ');
            $table->string('student_name')->comment('Họ tên học sinh tại thời điểm nộp');
            $table->string('student_code', 100)->nullable()->comment('Mã học sinh nếu có');
            $table->date('date_of_birth')->nullable()->comment('Ngày sinh học sinh');
            $table->string('current_class', 100)->nullable()->comment('Lớp hiện tại');
            $table->string('academic_year', 20)->nullable()->comment('Năm học');
            $table->string('relationship', 50)->nullable()->comment('Quan hệ người nộp với học sinh');
            $table->string('relationship_other')->nullable()->comment('Mô tả quan hệ khác');
            $table->string('status', 30)->default('pending')->comment('Trạng thái xử lý hồ sơ');
            $table->text('response')->nullable()->comment('Phản hồi được phép hiển thị cho người nộp');
            $table->string('rejection_reason_code', 50)->nullable()->comment('Mã lý do từ chối');
            $table->text('rejection_reason')->nullable()->comment('Chi tiết lý do từ chối');
            $table->timestamp('submitted_at')->comment('Thời điểm nộp hồ sơ');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete()->comment('Quản trị viên xử lý');
            $table->timestamp('processed_at')->nullable()->comment('Thời điểm hoàn tất xử lý');
            $table->unsignedInteger('version')->default(1)->comment('Phiên bản khóa lạc quan chống ghi đè đồng thời');
            $table->timestamps();

            $table->index(['status', 'submitted_at'], 'administrative_submissions_status_date_index');
            $table->index(['procedure_id', 'status', 'submitted_at'], 'administrative_submissions_filter_index');
            $table->index('phone');
            $table->index('email');
            $table->index('student_name');
            $table->index('student_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_submissions');
    }
};
