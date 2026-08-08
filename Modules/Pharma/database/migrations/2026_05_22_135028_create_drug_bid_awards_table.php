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
        Schema::create('pharma_drug_bid_awards', function (Blueprint $table) {
            $table->id();

            // Khóa ngoại liên kết danh mục thuốc gốc hệ thống
            $table->foreignId('medicine_id')
                ->nullable()
                ->constrained('pharma_medicines')
                ->onDelete('set null')
                ->comment('Liên kết đến danh mục thuốc hệ thống');

            // Thông tin chi tiết sản phẩm trúng thầu
            $table->string('medicine_name')->comment('Tên thuốc ghi nhận theo hồ sơ trúng thầu');
            $table->string('packaging_specification')->comment('Quy cách đóng gói thầu');
            $table->integer('quantity')->unsigned()->comment('Số lượng trúng thầu');
            $table->decimal('unit_price', 15, 2)->comment('Đơn giá trúng thầu');

            // Thông tin quản lý và pháp lý gói thầu (Bắt buộc nhập)
            $table->string('bidding_notice_code')->comment('Mã thông báo mời thầu (Bắt buộc)');
            $table->string('investor_name')->comment('Tên Chủ đầu tư (Bệnh viện / Sở y tế)');
            $table->string('decision_number')->comment('Số quyết định trúng thầu pháp lý');
            $table->date('decision_date')->comment('Ngày ban hành quyết định thầu');
            $table->integer('contract_duration_months')->unsigned()->comment('Thời hạn hiệu lực quy đổi (số tháng)');

            // Đơn vị trúng thầu & Tài liệu
            $table->string('winning_company_name')->comment('Tên doanh nghiệp trúng thầu');
            $table->string('decision_document_url')->nullable()->comment('Đường dẫn lưu trữ văn bản quyết định (PDF/Scan)');

            // Chỉ mục tìm kiếm đơn lẻ phục vụ bộ lọc (Filter Speed)
            $table->index('investor_name');
            $table->index('winning_company_name');

            // HÀNG RÀO CHỐNG TRÙNG LẶP: Composite Unique Index
            // Đảm bảo không thể trùng lặp cùng một vị trí thuốc, thuộc cùng một công ty trong một gói thầu
            $table->unique(
                ['bidding_notice_code', 'medicine_name', 'winning_company_name'],
                'unique_bid_award_item'
            );

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharma_drug_bid_awards');
    }
};
