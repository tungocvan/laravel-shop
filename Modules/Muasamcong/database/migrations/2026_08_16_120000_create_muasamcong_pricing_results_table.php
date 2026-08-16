<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_pricing_results', function (Blueprint $table): void {
            $table->id();
            $table->uuid('source_id')->unique();
            $table->string('type', 50)->nullable();
            $table->string('tab', 100)->nullable();
            $table->string('don_vi_tinh', 100)->nullable();
            $table->string('ma_tbmt', 100)->nullable()->index();
            $table->text('ten_cdt_bmt')->nullable();
            $table->string('ma_cdt', 100)->nullable();
            $table->json('winning_code')->nullable();
            $table->json('winning_name')->nullable();
            $table->string('bid_form', 100)->nullable();
            $table->string('medicines', 50)->nullable();
            $table->dateTime('ngay_dang_tai_kqlcnt')->nullable();
            $table->json('dia_diem')->nullable();
            $table->decimal('don_gia', 20, 4)->nullable();
            $table->string('ten_thuoc', 500)->nullable()->index();
            $table->string('ten_hoat_chat', 500)->nullable()->index();
            $table->text('nong_do')->nullable();
            $table->text('duong_dung')->nullable();
            $table->text('dang_bao_che')->nullable();
            $table->string('han_dung', 255)->nullable();
            $table->text('ten_co_so_san_xuat')->nullable();
            $table->string('nuoc_san_xuat', 255)->nullable();
            $table->text('quy_cach_dong_goi')->nullable();
            $table->decimal('so_luong', 20, 4)->nullable();
            $table->string('nhom_thuoc', 255)->nullable();
            $table->decimal('so_nha_thau_tham_du', 20, 8)->nullable();
            $table->text('so_quyet_dinh')->nullable();
            $table->dateTime('ngay_ban_hanh_quyet_dinh')->nullable();
            $table->text('gdklh_gpnk')->nullable();
            $table->json('raw_payload');
            $table->unsignedBigInteger('synced_by')->nullable()->index();
            $table->timestamp('synced_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_pricing_results');
    }
};
