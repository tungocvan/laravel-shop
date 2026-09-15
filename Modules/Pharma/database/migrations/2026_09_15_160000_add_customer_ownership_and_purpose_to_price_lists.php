<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_price_list_purposes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->foreignId('manager_user_id')->nullable()->after('partner_id')->constrained('users')->nullOnDelete();
            $table->foreignId('purpose_id')->nullable()->after('manager_user_id')->constrained('pharma_price_list_purposes')->nullOnDelete();
            $table->index(['partner_id', 'manager_user_id'], 'pharma_price_lists_partner_manager_idx');
        });

        $now = now();
        DB::table('pharma_price_list_purposes')->insert([
            ['code' => 'hospital-quotation', 'name' => 'Chào giá vào bệnh viện', 'description' => 'Dùng khi lập bảng giá để chào giá, thương thảo hoặc cung cấp chính sách giá cho bệnh viện/cơ sở khám chữa bệnh.', 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'pharma-company-quotation', 'name' => 'Chào giá Công ty Dược', 'description' => 'Dùng khi lập bảng giá thương mại cho công ty dược hoặc đơn vị phân phối.', 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'pharmacy-quotation', 'name' => 'Chào giá Nhà thuốc', 'description' => 'Dùng khi lập bảng giá bán/chào giá cho nhà thuốc hoặc chuỗi nhà thuốc.', 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'direct-customer-quotation', 'name' => 'Báo giá trực tiếp khách hàng', 'description' => 'Dùng cho báo giá trực tiếp theo yêu cầu của khách hàng.', 'is_active' => true, 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'contract-renewal', 'name' => 'Hợp đồng / tái ký', 'description' => 'Dùng cho chính sách giá phục vụ hợp đồng mới, gia hạn hoặc tái ký.', 'is_active' => true, 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->dropIndex('pharma_price_lists_partner_manager_idx');
            $table->dropConstrainedForeignId('purpose_id');
            $table->dropConstrainedForeignId('manager_user_id');
        });

        Schema::dropIfExists('pharma_price_list_purposes');
    }
};
