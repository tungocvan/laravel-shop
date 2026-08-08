<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->string('mhs')->unique();
            $table->string('status')->nullable();

            // BƯỚC 1: Thông tin học sinh
            $table->string('ho_va_ten_hoc_sinh')->nullable();
            $table->string('gioi_tinh')->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->string('dan_toc')->nullable();
            $table->string('ma_dinh_danh')->nullable();
            $table->string('quoc_tich')->nullable();
            $table->string('ton_giao')->nullable();
            $table->string('sdt_enetviet')->nullable();
            $table->string('noi_sinh')->nullable();
            $table->string('noi_sinh_px')->nullable();
            $table->string('noi_sinh_tt')->nullable();
            $table->string('noi_sinh_chi_tiet')->nullable();
            $table->string('noi_dang_ky_khai_sinh_px')->nullable();
            $table->string('noi_dang_ky_khai_sinh_tt')->nullable();            
            $table->string('que_quan')->nullable();
            $table->string('que_quan_px')->nullable();
            $table->string('que_quan_tt')->nullable();

            // BƯỚC 2: Địa chỉ
            $table->string('ttsn')->nullable(); // Thường trú Số nhà
            $table->string('ttd')->nullable();  // Thường trú Đường
            $table->string('ttkp')->nullable(); // Thường trú Khu phố
            $table->string('ttpx')->nullable(); // Thường trú Phường xã
            $table->string('ttttp')->nullable(); // Thường trú Tỉnh TP
            $table->text('dia_chi_thuong_tru')->nullable();
            $table->string('htsn')->nullable(); // Hiện tại Số nhà
            $table->string('htd')->nullable();
            $table->string('htkp')->nullable();
            $table->string('htpx')->nullable();
            $table->string('htttp')->nullable();
            $table->text('noi_o_hien_tai')->nullable();

            // BƯỚC 3: Thông tin bổ sung
            $table->string('o_chung_voi')->nullable();
            $table->string('quan_he_nguoi_nuoi_duong')->nullable();
            $table->integer('con_thu')->nullable();
            $table->integer('ts_anh_chi_em')->nullable();
            $table->string('hoan_thanh_lop_la')->nullable();
            $table->string('truong_mam_non')->nullable();
            $table->text('kha_nang_hoc_sinh')->nullable();
            $table->text('suc_khoe_can_luu_y')->nullable();

            // BƯỚC 4: Cha - Mẹ - Giám hộ
            $table->string('ho_ten_cha')->nullable(); $table->string('nam_sinh_cha')->nullable();
            $table->string('tdvh_cha')->nullable(); $table->string('tdcm_cha')->nullable();
            $table->string('nghe_nghiep_cha')->nullable(); $table->string('chuc_vu_cha')->nullable();
            $table->string('dien_thoai_cha')->nullable(); $table->string('cccd_cha')->nullable();

            $table->string('ho_ten_me')->nullable(); $table->string('nam_sinh_me')->nullable();
            $table->string('tdvh_me')->nullable(); $table->string('tdcm_me')->nullable();
            $table->string('nghe_nghiep_me')->nullable(); $table->string('chuc_vu_me')->nullable();
            $table->string('dien_thoai_me')->nullable(); $table->string('cccd_me')->nullable();

            $table->string('ho_ten_nguoi_giam_ho')->nullable();
            $table->string('quan_he_giam_ho')->nullable();
            $table->string('dien_thoai_giam_ho')->nullable();
            $table->string('cccd_giam_ho')->nullable();

            // BƯỚC 5: Đăng ký & Cam kết
            $table->text('anh_chi_ruot_trong_truong')->nullable();
            $table->string('thanh_phan_gia_dinh')->nullable();
            $table->string('loai_lop_dang_ky')->nullable();
            $table->boolean('ck_goc_hoc_tap')->default(false);
            $table->boolean('ck_sach_vo')->default(false);
            $table->boolean('ck_hop_ph')->default(false);
            $table->boolean('ck_tham_gia_hd')->default(false);
            $table->boolean('ck_gan_gui')->default(false);
            $table->string('ngay_lam_don')->nullable();
            $table->string('nguoi_lam_don')->nullable();

            //Phần sắp xếp lớp
            $table->string('lop')->nullable();
            $table->string('gvcn')->nullable();
            $table->string('bao_mau')->nullable();

            $table->string('pdf_path')->nullable();
            $table->string('word_path')->nullable();

            $table->timestamps();
        });
    }

    public function down() { Schema::dropIfExists('admission_applications'); }
};
